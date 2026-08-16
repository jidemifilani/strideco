<?php
require_once __DIR__ . '/../config/config.php';

function start_session_if_needed(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function base_url(string $path = ''): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

function full_base_url(string $path = ''): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . base_url($path);
}

// ---------------------------------------------------------------
// Site settings (theme + content, editable from Admin → Settings)
// ---------------------------------------------------------------

function get_all_settings(PDO $pdo): array {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach ($pdo->query('SELECT setting_key, setting_value FROM settings') as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache;
}

function get_setting(PDO $pdo, string $key, string $default = ''): string {
    $settings = get_all_settings($pdo);
    return ($settings[$key] ?? '') !== '' ? $settings[$key] : $default;
}

// ---------------------------------------------------------------
// Order status history (powers the real-time order timeline)
// ---------------------------------------------------------------

function log_order_status(PDO $pdo, int $orderId, string $status, ?string $note = null): void {
    $stmt = $pdo->prepare('INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)');
    $stmt->execute([$orderId, $status, $note]);
}

function get_order_timeline(PDO $pdo, int $orderId): array {
    $stmt = $pdo->prepare('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC, id ASC');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

/**
 * Renders the order timeline as a vertical step tracker. Known statuses get a
 * fixed order/icon; anything else (e.g. a custom admin note) still shows up
 * chronologically so nothing is silently dropped.
 */
function order_timeline_html(array $history, string $currentPaymentStatus): string {
    $labels = [
        'order_placed' => 'Order placed',
        'paid' => 'Payment confirmed',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'payment_failed' => 'Payment failed',
        'refunded' => 'Refunded',
    ];

    $html = '<ul class="order-timeline">';
    foreach ($history as $i => $entry) {
        $isLast = $i === count($history) - 1;
        $label = $labels[$entry['status']] ?? ucfirst(str_replace('_', ' ', $entry['status']));
        $isBad = in_array($entry['status'], ['cancelled', 'payment_failed', 'refunded'], true);
        $stateClass = $isLast ? ($isBad ? 'is-bad' : 'is-current') : 'is-done';
        $time = date('d M Y, H:i', strtotime($entry['created_at']));
        $note = $entry['note'] ? '<p class="order-timeline-note">' . htmlspecialchars($entry['note']) . '</p>' : '';
        $html .= '<li class="' . $stateClass . '">'
            . '<span class="order-timeline-dot"></span>'
            . '<div class="order-timeline-body"><strong>' . htmlspecialchars($label) . '</strong>'
            . '<span class="order-timeline-time">' . htmlspecialchars($time) . '</span>'
            . $note
            . '</div></li>';
    }
    $html .= '</ul>';
    return $html;
}

// ---------------------------------------------------------------
// Automation: abandoned-order cleanup
// ---------------------------------------------------------------

/**
 * Auto-fails orders left "pending" payment for too long (customer abandoned
 * checkout at Paystack, or never completed it). Safe to run repeatedly.
 */
function cleanup_abandoned_orders(PDO $pdo, int $hoursThreshold = 24): int {
    $cutoff = date('Y-m-d H:i:s', time() - $hoursThreshold * 3600);
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE payment_status = 'pending' AND created_at < ?");
    $stmt->execute([$cutoff]);
    $orderIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    if (!$orderIds) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE id IN ($placeholders)")->execute($orderIds);

    foreach ($orderIds as $orderId) {
        log_order_status($pdo, $orderId, 'payment_failed', "Automatically cancelled — payment not completed within {$hoursThreshold} hours.");
    }

    return count($orderIds);
}

/**
 * Opportunistic trigger: runs the cleanup at most once an hour, kicked off by
 * a normal admin page load rather than needing a real cron job to exist. A
 * genuine cron/Task Scheduler entry running cron/cleanup-orders.php is the
 * more reliable way to do this in production (see README) — this is the
 * fallback that makes it work automatically even without one configured.
 */
function maybe_run_scheduled_cleanup(PDO $pdo): void {
    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->execute(['last_cleanup_run']);
    $last = $stmt->fetchColumn();
    if ($last !== false && strtotime($last) > time() - 3600) {
        return;
    }
    cleanup_abandoned_orders($pdo);
    $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    )->execute(['last_cleanup_run', date('Y-m-d H:i:s')]);
}

// ---------------------------------------------------------------
// Automation: abandoned-cart capture & recovery emails
// ---------------------------------------------------------------

/**
 * Saves a snapshot of the customer's current cart against their email, as
 * soon as it's known (typed into the checkout email field) but before the
 * order is actually placed. One row per email — a later call overwrites the
 * previous snapshot and resets reminder/recovered state, since it means
 * they came back and are (still) mid-checkout with a fresh cart.
 */
function capture_abandoned_cart(PDO $pdo, string $email, array $items, float $subtotal): void {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$items) {
        return;
    }

    $snapshot = array_map(function ($line) {
        return [
            'product_id' => $line['product']['id'],
            'variant_id' => $line['variant']['id'] ?? null,
            'name' => $line['product']['name'],
            'slug' => $line['product']['slug'],
            'size' => $line['size'],
            'qty' => $line['qty'],
            'price' => (float) $line['product']['price'],
        ];
    }, $items);

    $token = bin2hex(random_bytes(24));

    $pdo->prepare(
        'INSERT INTO abandoned_carts (email, token, cart_snapshot, subtotal, reminder_sent_at, recovered_at)
         VALUES (?, ?, ?, ?, NULL, NULL)
         ON DUPLICATE KEY UPDATE token = VALUES(token), cart_snapshot = VALUES(cart_snapshot),
             subtotal = VALUES(subtotal), reminder_sent_at = NULL, recovered_at = NULL'
    )->execute([strtolower($email), $token, json_encode($snapshot), $subtotal]);
}

function mark_abandoned_cart_recovered(PDO $pdo, string $email): void {
    $pdo->prepare('UPDATE abandoned_carts SET recovered_at = NOW() WHERE email = ? AND recovered_at IS NULL')
        ->execute([strtolower($email)]);
}

function get_abandoned_cart_by_token(PDO $pdo, string $token): ?array {
    if ($token === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM abandoned_carts WHERE token = ? AND recovered_at IS NULL');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Emails everyone whose cart snapshot is older than $hoursThreshold and
 * hasn't been reminded or recovered yet, with a link that restores their
 * exact cart. Returns how many reminders were sent.
 */
function send_abandoned_cart_reminders(PDO $pdo, int $hoursThreshold = 2): int {
    $cutoff = date('Y-m-d H:i:s', time() - $hoursThreshold * 3600);
    $stmt = $pdo->prepare(
        'SELECT * FROM abandoned_carts WHERE reminder_sent_at IS NULL AND recovered_at IS NULL AND updated_at < ?'
    );
    $stmt->execute([$cutoff]);
    $carts = $stmt->fetchAll();

    $sent = 0;
    foreach ($carts as $cart) {
        $items = json_decode($cart['cart_snapshot'], true) ?: [];
        if (!$items) {
            continue;
        }

        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr><td style="padding:6px 0;border-bottom:1px solid #E8E4DE;">'
                . htmlspecialchars($item['name']) . ' (' . htmlspecialchars($item['size']) . ') &times; ' . (int) $item['qty']
                . '</td><td style="padding:6px 0;border-bottom:1px solid #E8E4DE;text-align:right;">' . format_price($item['price'] * $item['qty']) . '</td></tr>';
        }

        $recoveryUrl = full_base_url('cart.php?restore=' . urlencode($cart['token']));
        $body = '<p>You left some great picks in your cart — they\'re still waiting for you.</p>'
            . '<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:0.92rem;">' . $rows . '</table>'
            . '<p style="text-align:right;font-weight:700;">Subtotal: ' . format_price((float) $cart['subtotal']) . '</p>'
            . '<p><a href="' . $recoveryUrl . '" style="display:inline-block;padding:10px 22px;background:' . htmlspecialchars(get_setting($pdo, 'accent_color', '#FF5A1F')) . ';color:#fff;border-radius:999px;text-decoration:none;font-weight:600;">Restore my cart</a></p>';

        $ok = send_email($cart['email'], $cart['email'], "You left something in your cart", email_layout('Still thinking it over?', $body, $pdo));
        if ($ok) {
            $sent++;
        }
        $pdo->prepare('UPDATE abandoned_carts SET reminder_sent_at = NOW() WHERE id = ?')->execute([$cart['id']]);
    }

    return $sent;
}

/**
 * Opportunistic trigger (same rate-limited-via-settings pattern as
 * maybe_run_scheduled_cleanup): runs at most once an hour off a normal admin
 * page load, so reminders go out even without a real cron job configured.
 * cron/send-abandoned-cart-emails.php is the reliable production path.
 */
function maybe_run_abandoned_cart_reminders(PDO $pdo): void {
    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->execute(['last_abandoned_cart_run']);
    $last = $stmt->fetchColumn();
    if ($last !== false && strtotime($last) > time() - 3600) {
        return;
    }
    send_abandoned_cart_reminders($pdo);
    $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    )->execute(['last_abandoned_cart_run', date('Y-m-d H:i:s')]);
}

function hex_tint(string $hex, float $amountToWhite): string {
    $hex = ltrim($hex, '#');
    [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    $r = (int) round($r + (255 - $r) * $amountToWhite);
    $g = (int) round($g + (255 - $g) * $amountToWhite);
    $b = (int) round($b + (255 - $b) * $amountToWhite);
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function hex_shade(string $hex, float $amountToBlack): string {
    $hex = ltrim($hex, '#');
    [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    $r = (int) round($r * (1 - $amountToBlack));
    $g = (int) round($g * (1 - $amountToBlack));
    $b = (int) round($b * (1 - $amountToBlack));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function format_price(float $amount): string {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

const FREE_SHIPPING_THRESHOLD = 50000.0;

function shipping_nudge_html(float $subtotal): string {
    if ($subtotal <= 0) {
        return '';
    }
    $remaining = FREE_SHIPPING_THRESHOLD - $subtotal;
    if ($remaining <= 0) {
        return '<div class="shipping-nudge success"><p>&#127881; You\'ve unlocked free shipping!</p></div>';
    }
    $progress = min(100, ($subtotal / FREE_SHIPPING_THRESHOLD) * 100);
    $remainingLabel = htmlspecialchars(format_price($remaining));
    return <<<HTML
    <div class="shipping-nudge">
      <div class="shipping-progress-bar"><div class="shipping-progress-fill" style="width:{$progress}%;"></div></div>
      <p>Add <strong>{$remainingLabel}</strong> more for free shipping!</p>
    </div>
    HTML;
}

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    return $text !== '' ? $text : 'n-a';
}

function generate_order_ref(): string {
    return 'SC' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

// ---------------------------------------------------------------
// Shipping zones & tax
// ---------------------------------------------------------------

function get_shipping_zones(PDO $pdo): array {
    return $pdo->query('SELECT * FROM shipping_zones ORDER BY is_default ASC, sort_order ASC, id ASC')->fetchAll();
}

/**
 * Picks the shipping fee for a delivery city/state: the first non-default
 * zone whose comma-separated `states` list contains the city (case-
 * insensitive substring match), else the zone marked is_default, else the
 * SHIPPING_FEE constant if no zones are configured at all (keeps checkout
 * working even on a fresh install before an admin sets up real zones).
 *
 * @return array{zone: ?array, fee: float}
 */
function resolve_shipping_fee(PDO $pdo, string $city): array {
    $zones = get_shipping_zones($pdo);
    if (!$zones) {
        return ['zone' => null, 'fee' => (float) SHIPPING_FEE];
    }

    $city = trim($city);
    $default = null;
    foreach ($zones as $zone) {
        if ($zone['is_default']) {
            $default = $zone;
            continue;
        }
        if ($city === '' || empty($zone['states'])) {
            continue;
        }
        $states = array_map('trim', explode(',', strtolower($zone['states'])));
        if (in_array(strtolower($city), $states, true)) {
            return ['zone' => $zone, 'fee' => (float) $zone['fee']];
        }
    }

    return $default
        ? ['zone' => $default, 'fee' => (float) $default['fee']]
        : ['zone' => $zones[0], 'fee' => (float) $zones[0]['fee']];
}

function get_tax_rate_percent(PDO $pdo): float {
    return (float) get_setting($pdo, 'tax_rate_percent', '0');
}

function calculate_tax(PDO $pdo, float $taxableAmount): float {
    $rate = get_tax_rate_percent($pdo);
    return $rate > 0 ? round($taxableAmount * ($rate / 100), 2) : 0.0;
}

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

// ---------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------

function csrf_token(): string {
    start_session_if_needed();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): bool {
    start_session_if_needed();
    $sent = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}

// ---------------------------------------------------------------
// Product images (falls back to a generated placeholder graphic)
// ---------------------------------------------------------------

function product_image_url(array $product): string {
    if (!empty($product['image']) && file_exists(__DIR__ . '/../assets/uploads/' . $product['image'])) {
        return base_url('assets/uploads/' . $product['image']);
    }
    $params = http_build_query([
        'name' => $product['name'],
        'accent' => $product['accent_color'] ?? '#FF6A1A',
        'seed' => $product['id'] ?? 0,
    ]);
    return base_url('image.php?' . $params);
}

/**
 * Returns a product's color variants (empty array if it has none — see the
 * product_variants table comment in database/upgrade_v3.sql for the
 * backward-compatible "no variants = single color from products.color"
 * design). Each variant carries its own 'sizes' list (size => stock).
 */
function get_product_variants(PDO $pdo, int $productId): array {
    $stmt = $pdo->prepare('SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order, id');
    $stmt->execute([$productId]);
    $variants = $stmt->fetchAll();
    if (!$variants) {
        return [];
    }

    $variantIds = array_column($variants, 'id');
    $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
    $sizesStmt = $pdo->prepare("SELECT * FROM variant_sizes WHERE variant_id IN ($placeholders) ORDER BY CAST(size AS UNSIGNED)");
    $sizesStmt->execute($variantIds);
    $sizesByVariant = [];
    foreach ($sizesStmt->fetchAll() as $row) {
        $sizesByVariant[$row['variant_id']][$row['size']] = (int) $row['stock'];
    }

    foreach ($variants as &$variant) {
        $variant['sizes'] = $sizesByVariant[$variant['id']] ?? [];
    }
    unset($variant);

    return $variants;
}

function variant_image_url(array $variant, array $product): string {
    if (!empty($variant['image']) && file_exists(__DIR__ . '/../assets/uploads/' . $variant['image'])) {
        return base_url('assets/uploads/' . $variant['image']);
    }
    return product_image_url($product);
}

// ---------------------------------------------------------------
// Product card (shared markup for home / shop / related products)
// ---------------------------------------------------------------

function product_card_html(array $product, array $wishlistIds = []): string {
    $url = base_url('product.php?slug=' . urlencode($product['slug']));
    $img = product_image_url($product);
    $name = htmlspecialchars($product['name']);
    $cat = htmlspecialchars($product['category_name'] ?? '');
    $price = format_price((float) $product['price']);
    $productId = (int) $product['id'];
    $inWishlist = in_array($productId, $wishlistIds, true);
    $redirectTo = htmlspecialchars($_SERVER['REQUEST_URI'] ?? base_url('shop.php'));
    $csrf = htmlspecialchars(csrf_token());

    $badge = '';
    if (!empty($product['is_preorder'])) {
        $badge = '<span class="product-badge preorder-badge">Pre-order</span>';
    } elseif (!empty($product['is_featured'])) {
        $badge = '<span class="product-badge">Bestseller</span>';
    } elseif (!empty($product['created_at']) && strtotime($product['created_at']) > strtotime('-14 days')) {
        $badge = '<span class="product-badge">New</span>';
    }

    $ratingHtml = '';
    if (isset($product['rating_avg']) && (float) $product['rating_avg'] > 0) {
        $ratingHtml = '<div class="rating-summary">' . star_rating_html((float) $product['rating_avg'], 12)
            . '<span>(' . (int) $product['rating_count'] . ')</span></div>';
    }

    $wishlistActionUrl = base_url('wishlist-actions.php');
    $wishlistBtnClass = $inWishlist ? 'wishlist-btn active' : 'wishlist-btn';
    $slugAttr = htmlspecialchars($product['slug']);

    return <<<HTML
    <div class="product-card">
      <div class="product-thumb">
        {$badge}
        <a href="{$url}" class="product-thumb-link" tabindex="-1"><img src="{$img}" alt="{$name}" loading="lazy"></a>
        <form action="{$wishlistActionUrl}" method="post" class="wishlist-form">
          <input type="hidden" name="csrf_token" value="{$csrf}">
          <input type="hidden" name="product_id" value="{$productId}">
          <input type="hidden" name="redirect_to" value="{$redirectTo}">
          <button type="submit" class="{$wishlistBtnClass}" aria-label="Toggle wishlist for {$name}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 20.5s-7.5-4.6-9.8-9C.7 8.1 2 4.5 5.4 3.6c2-.5 4 .3 5.1 2 .3.4.9.4 1.2 0 1.1-1.7 3.1-2.5 5.1-2 3.4.9 4.7 4.5 3.2 7.9-2.3 4.4-9.8 9-9.8 9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
          </button>
        </form>
        <button type="button" class="quick-view-btn" data-slug="{$slugAttr}">Quick View</button>
      </div>
      <div class="product-info">
        <span class="product-cat">{$cat}</span>
        <h3 class="product-name"><a href="{$url}">{$name}</a></h3>
        {$ratingHtml}
        <div class="product-bottom">
          <span class="product-price">{$price}</span>
          <a href="{$url}" class="product-add" aria-label="View {$name}">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          </a>
        </div>
      </div>
    </div>
    HTML;
}

// ---------------------------------------------------------------
// Cart (session-based, keyed by "productId-size")
// ---------------------------------------------------------------

function cart_key(int $productId, string $size, ?int $variantId = null): string {
    return $variantId ? $productId . '-v' . $variantId . '-' . $size : $productId . '-' . $size;
}

function get_cart_raw(): array {
    start_session_if_needed();
    return $_SESSION['cart'] ?? [];
}

function add_to_cart(int $productId, string $size, int $qty, ?int $variantId = null): void {
    start_session_if_needed();
    $key = cart_key($productId, $size, $variantId);
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$key] = ['product_id' => $productId, 'size' => $size, 'qty' => $qty, 'variant_id' => $variantId];
    }
}

function update_cart_item(string $key, int $qty): void {
    start_session_if_needed();
    if (!isset($_SESSION['cart'][$key])) {
        return;
    }
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
    } else {
        $_SESSION['cart'][$key]['qty'] = $qty;
    }
}

function remove_from_cart(string $key): void {
    start_session_if_needed();
    unset($_SESSION['cart'][$key]);
}

function clear_cart(): void {
    start_session_if_needed();
    $_SESSION['cart'] = [];
}

/**
 * Builds the cart with live product data (name, price, image, stock) attached,
 * dropping any lines whose product no longer exists.
 */
function get_cart_details(PDO $pdo): array {
    $raw = get_cart_raw();
    if (empty($raw)) {
        return [];
    }

    $productIds = array_unique(array_column($raw, 'product_id'));
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $products = [];
    foreach ($stmt->fetchAll() as $row) {
        $products[$row['id']] = $row;
    }

    $variantIds = array_unique(array_filter(array_column($raw, 'variant_id')));
    $variants = [];
    if ($variantIds) {
        $vPlaceholders = implode(',', array_fill(0, count($variantIds), '?'));
        $vStmt = $pdo->prepare("SELECT * FROM product_variants WHERE id IN ($vPlaceholders)");
        $vStmt->execute(array_values($variantIds));
        foreach ($vStmt->fetchAll() as $row) {
            $variants[$row['id']] = $row;
        }
    }

    $details = [];
    foreach ($raw as $key => $line) {
        if (!isset($products[$line['product_id']])) {
            continue;
        }
        $product = $products[$line['product_id']];
        $variantId = $line['variant_id'] ?? null;
        $details[] = [
            'key' => $key,
            'product' => $product,
            'size' => $line['size'],
            'qty' => $line['qty'],
            'line_total' => $product['price'] * $line['qty'],
            'variant' => $variantId && isset($variants[$variantId]) ? $variants[$variantId] : null,
        ];
    }
    return $details;
}

function cart_count(): int {
    $raw = get_cart_raw();
    $count = 0;
    foreach ($raw as $line) {
        $count += $line['qty'];
    }
    return $count;
}

function cart_subtotal(PDO $pdo): float {
    $subtotal = 0.0;
    foreach (get_cart_details($pdo) as $line) {
        $subtotal += $line['line_total'];
    }
    return $subtotal;
}

// ---------------------------------------------------------------
// Flash messages
// ---------------------------------------------------------------

function set_flash(string $type, string $message): void {
    start_session_if_needed();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function render_flash(): void {
    start_session_if_needed();
    if (empty($_SESSION['flash'])) {
        return;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $icon = $flash['type'] === 'success'
        ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
    printf(
        '<div class="toast toast-%s" role="status"><span class="toast-icon">%s</span><span class="toast-msg">%s</span><button type="button" class="toast-close" aria-label="Dismiss">&times;</button></div>',
        htmlspecialchars($flash['type']),
        $icon,
        htmlspecialchars($flash['message'])
    );
}

// ---------------------------------------------------------------
// Admin auth guard
// ---------------------------------------------------------------

function require_admin(): void {
    start_session_if_needed();
    if (empty($_SESSION['admin_id'])) {
        redirect(base_url('admin/login.php'));
    }
}

function is_super_admin(): bool {
    start_session_if_needed();
    return ($_SESSION['admin_role'] ?? '') === 'super_admin';
}

function require_super_admin(): void {
    require_admin();
    if (!is_super_admin()) {
        set_flash('error', "You don't have permission to do that.");
        redirect(base_url('admin/index.php'));
    }
}

// ---------------------------------------------------------------
// Login throttling (shared by admin + customer login forms)
// ---------------------------------------------------------------

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_MINUTES = 15;

function is_account_locked(array $account): bool {
    return !empty($account['locked_until']) && strtotime($account['locked_until']) > time();
}

function register_failed_login(PDO $pdo, string $table, int $id, int $currentAttempts): void {
    if (!in_array($table, ['admins', 'customers'], true)) {
        return;
    }
    $attempts = $currentAttempts + 1;
    if ($attempts >= LOGIN_MAX_ATTEMPTS) {
        $lockUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_MINUTES * 60);
        $pdo->prepare("UPDATE $table SET failed_attempts = ?, locked_until = ? WHERE id = ?")
            ->execute([$attempts, $lockUntil, $id]);
    } else {
        $pdo->prepare("UPDATE $table SET failed_attempts = ? WHERE id = ?")->execute([$attempts, $id]);
    }
}

function reset_login_attempts(PDO $pdo, string $table, int $id): void {
    if (!in_array($table, ['admins', 'customers'], true)) {
        return;
    }
    $pdo->prepare("UPDATE $table SET failed_attempts = 0, locked_until = NULL WHERE id = ?")->execute([$id]);
}

function lockout_message(array $account): string {
    $minutesLeft = max(1, (int) ceil((strtotime($account['locked_until']) - time()) / 60));
    return "Too many failed attempts. Try again in {$minutesLeft} minute" . ($minutesLeft === 1 ? '' : 's') . '.';
}

// ---------------------------------------------------------------
// Spam honeypot (invisible field — bots fill it, humans never see it)
// ---------------------------------------------------------------

function honeypot_field(): string {
    return '<div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">'
        . '<input type="text" name="website" tabindex="-1" autocomplete="off"></div>';
}

function honeypot_triggered(): bool {
    return !empty($_POST['website']);
}

// ---------------------------------------------------------------
// Customer accounts
// ---------------------------------------------------------------

function is_customer_logged_in(): bool {
    start_session_if_needed();
    return !empty($_SESSION['customer_id']);
}

function current_customer_id(): ?int {
    start_session_if_needed();
    return $_SESSION['customer_id'] ?? null;
}

function require_customer(): void {
    start_session_if_needed();
    if (empty($_SESSION['customer_id'])) {
        set_flash('error', 'Please log in to continue.');
        redirect(base_url('account/login.php'));
    }
}

// ---------------------------------------------------------------
// Wishlist (session-based for guests, synced to DB for logged-in customers)
// ---------------------------------------------------------------

function wishlist_session_ids(): array {
    start_session_if_needed();
    return $_SESSION['wishlist'] ?? [];
}

function add_to_wishlist_session(int $productId): void {
    start_session_if_needed();
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    if (!in_array($productId, $_SESSION['wishlist'], true)) {
        $_SESSION['wishlist'][] = $productId;
    }
}

function remove_from_wishlist_session(int $productId): void {
    start_session_if_needed();
    $_SESSION['wishlist'] = array_values(array_diff($_SESSION['wishlist'] ?? [], [$productId]));
}

function sync_wishlist_to_db(PDO $pdo, int $customerId): void {
    $sessionIds = wishlist_session_ids();
    if (!$sessionIds) {
        return;
    }
    $stmt = $pdo->prepare('INSERT IGNORE INTO wishlists (customer_id, product_id) VALUES (?, ?)');
    foreach ($sessionIds as $productId) {
        $stmt->execute([$customerId, (int) $productId]);
    }
    start_session_if_needed();
    $_SESSION['wishlist'] = [];
}

/** Returns the current visitor's wishlist product IDs (DB-backed if logged in, session otherwise). */
function get_wishlist_ids(PDO $pdo): array {
    if (is_customer_logged_in()) {
        $stmt = $pdo->prepare('SELECT product_id FROM wishlists WHERE customer_id = ?');
        $stmt->execute([current_customer_id()]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
    return array_map('intval', wishlist_session_ids());
}

function wishlist_count(PDO $pdo): int {
    return count(get_wishlist_ids($pdo));
}

function is_in_wishlist(PDO $pdo, int $productId): bool {
    return in_array($productId, get_wishlist_ids($pdo), true);
}

// ---------------------------------------------------------------
// Coupons
// ---------------------------------------------------------------

/**
 * @return array{valid: bool, message: string, coupon: ?array, discount: float}
 */
function validate_coupon(PDO $pdo, string $code, float $subtotal): array {
    $code = strtoupper(trim($code));
    if ($code === '') {
        return ['valid' => false, 'message' => 'Enter a coupon code.', 'coupon' => null, 'discount' => 0.0];
    }

    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code = ?');
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon || $coupon['status'] !== 'active') {
        return ['valid' => false, 'message' => 'That coupon code is not valid.', 'coupon' => null, 'discount' => 0.0];
    }
    if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < strtotime('today')) {
        return ['valid' => false, 'message' => 'That coupon has expired.', 'coupon' => null, 'discount' => 0.0];
    }
    if ($coupon['max_uses'] !== null && (int) $coupon['used_count'] >= (int) $coupon['max_uses']) {
        return ['valid' => false, 'message' => 'That coupon has reached its usage limit.', 'coupon' => null, 'discount' => 0.0];
    }
    if ($subtotal < (float) $coupon['min_subtotal']) {
        return [
            'valid' => false,
            'message' => 'This coupon needs a subtotal of at least ' . format_price((float) $coupon['min_subtotal']) . '.',
            'coupon' => null,
            'discount' => 0.0,
        ];
    }

    $discount = $coupon['type'] === 'percent'
        ? round($subtotal * ((float) $coupon['value'] / 100), 2)
        : (float) $coupon['value'];
    $discount = min($discount, $subtotal);

    return ['valid' => true, 'message' => 'Coupon applied!', 'coupon' => $coupon, 'discount' => $discount];
}

// ---------------------------------------------------------------
// Gift cards
// ---------------------------------------------------------------

function generate_gift_card_code(): string {
    return 'GC' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
}

/**
 * @return array{valid: bool, message: string, giftCard: ?array}
 */
function validate_gift_card(PDO $pdo, string $code): array {
    $code = strtoupper(trim($code));
    if ($code === '') {
        return ['valid' => false, 'message' => 'Enter a gift card code.', 'giftCard' => null];
    }

    $stmt = $pdo->prepare('SELECT * FROM gift_cards WHERE code = ?');
    $stmt->execute([$code]);
    $giftCard = $stmt->fetch();

    if (!$giftCard || $giftCard['status'] !== 'active') {
        return ['valid' => false, 'message' => 'That gift card code is not valid.', 'giftCard' => null];
    }
    if (!empty($giftCard['expires_at']) && strtotime($giftCard['expires_at']) < strtotime('today')) {
        return ['valid' => false, 'message' => 'That gift card has expired.', 'giftCard' => null];
    }
    if ((float) $giftCard['balance'] <= 0) {
        return ['valid' => false, 'message' => 'That gift card has no remaining balance.', 'giftCard' => null];
    }

    return ['valid' => true, 'message' => 'Gift card applied!', 'giftCard' => $giftCard];
}

// ---------------------------------------------------------------
// Product reviews
// ---------------------------------------------------------------

function get_rating_summary(PDO $pdo, int $productId): array {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS count, COALESCE(AVG(rating), 0) AS avg
         FROM reviews WHERE product_id = ? AND status = 'approved'"
    );
    $stmt->execute([$productId]);
    $row = $stmt->fetch();
    return ['count' => (int) $row['count'], 'avg' => round((float) $row['avg'], 1)];
}

function star_rating_html(float $avg, int $size = 14): string {
    $html = '<span class="star-rating" aria-label="' . htmlspecialchars(number_format($avg, 1)) . ' out of 5 stars">';
    for ($i = 1; $i <= 5; $i++) {
        $filled = $avg >= $i - 0.25;
        $html .= '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 20 20" fill="' . ($filled ? 'currentColor' : 'none') . '" stroke="currentColor" stroke-width="1.3"><path d="M10 1.5l2.6 5.6 6 .7-4.5 4.1 1.2 6-5.3-3-5.3 3 1.2-6L1.4 7.8l6-.7L10 1.5Z" stroke-linejoin="round"/></svg>';
    }
    $html .= '</span>';
    return $html;
}

function mail_configured(): bool {
    return SMTP_HOST !== 'smtp.example.com'
        && SMTP_USERNAME !== 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
        && SMTP_PASSWORD !== 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
}

/**
 * Sends an email via SMTP (PHPMailer). Returns false without sending (and logs
 * to error_log) if SMTP_* in config.php is still on placeholder values, or if
 * vendor/autoload.php hasn't been installed — same "safe no-op until real
 * credentials are supplied" pattern as Paystack.
 */
function send_email(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class) || !mail_configured()) {
        error_log("send_email skipped (not configured): to={$toEmail} subject=\"{$subject}\"");
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->Port = SMTP_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_PORT === 465
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = trim(strip_tags(str_ireplace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody)));
        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log('send_email failed: ' . $mail->ErrorInfo);
        return false;
    }
}

function send_order_confirmation_email(PDO $pdo, array $order): bool {
    $itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $itemsStmt->execute([$order['id']]);
    $items = $itemsStmt->fetchAll();

    $rows = '';
    $hasPreorderItems = false;
    foreach ($items as $item) {
        $variantLabel = !empty($item['variant_color']) ? htmlspecialchars($item['variant_color']) . ' / ' : '';
        $preorderLabel = '';
        if (!empty($item['is_preorder'])) {
            $hasPreorderItems = true;
            $preorderLabel = ' <span style="color:#1A56C4;font-weight:700;font-size:0.75rem;text-transform:uppercase;">[Pre-order'
                . ($item['preorder_available_at'] ? ' — expected ' . date('d M Y', strtotime($item['preorder_available_at'])) : '') . ']</span>';
        }
        $rows .= '<tr><td style="padding:6px 0;border-bottom:1px solid #E8E4DE;">'
            . htmlspecialchars($item['product_name']) . ' (' . $variantLabel . htmlspecialchars($item['size']) . ') &times; ' . (int) $item['quantity'] . $preorderLabel
            . '</td><td style="padding:6px 0;border-bottom:1px solid #E8E4DE;text-align:right;">' . format_price((float) $item['price'] * (int) $item['quantity']) . '</td></tr>';
    }

    $body = '<p>Hi ' . htmlspecialchars($order['customer_name']) . ',</p>'
        . '<p>Thanks for your order! We\'ve received your payment and we\'re getting it ready.</p>'
        . '<p><strong>Order reference:</strong> ' . htmlspecialchars($order['order_ref']) . '</p>'
        . ($hasPreorderItems ? '<p style="background:#E6F0FF;color:#1A56C4;padding:10px 14px;border-radius:8px;font-size:0.9rem;">📦 This order includes one or more pre-order items — those will ship once they become available, separately if needed.</p>' : '')
        . '<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:0.92rem;">' . $rows . '</table>'
        . '<p style="text-align:right;font-weight:700;">Total: ' . format_price((float) $order['total']) . '</p>'
        . '<p><a href="' . full_base_url('track-order.php?ref=' . urlencode($order['order_ref']) . '&email=' . urlencode($order['email'])) . '" style="color:' . htmlspecialchars(get_setting($pdo, 'accent_color', '#FF5A1F')) . ';">Track your order</a></p>';

    return send_email(
        $order['email'],
        $order['customer_name'],
        'Order confirmed — ' . $order['order_ref'],
        email_layout('Order confirmed', $body, $pdo)
    );
}

const ORDER_STATUS_EMAIL_LABELS = [
    'shipped' => 'Your order has shipped',
    'delivered' => 'Your order has been delivered',
    'cancelled' => 'Your order was cancelled',
];

function send_order_refunded_email(PDO $pdo, array $order, float $amount, ?string $reason = null): bool {
    $body = '<p>Hi ' . htmlspecialchars($order['customer_name']) . ',</p>'
        . '<p>We\'ve refunded <strong>' . format_price($amount) . '</strong> for order <strong>' . htmlspecialchars($order['order_ref']) . '</strong>.</p>'
        . ($reason ? '<p style="color:#4A4540;">' . htmlspecialchars($reason) . '</p>' : '')
        . '<p>It can take a few business days to reflect on your statement, depending on your bank.</p>';

    return send_email($order['email'], $order['customer_name'], 'Refund processed — ' . $order['order_ref'], email_layout('Refund processed', $body, $pdo));
}

function send_order_status_email(PDO $pdo, array $order, string $status, ?string $note): bool {
    if (!isset(ORDER_STATUS_EMAIL_LABELS[$status])) {
        return false;
    }
    $title = ORDER_STATUS_EMAIL_LABELS[$status];
    $body = '<p>Hi ' . htmlspecialchars($order['customer_name']) . ',</p>'
        . '<p>' . htmlspecialchars($title) . ' — order <strong>' . htmlspecialchars($order['order_ref']) . '</strong>.</p>'
        . ($note ? '<p style="color:#4A4540;">' . htmlspecialchars($note) . '</p>' : '')
        . '<p><a href="' . full_base_url('track-order.php?ref=' . urlencode($order['order_ref']) . '&email=' . urlencode($order['email'])) . '" style="color:' . htmlspecialchars(get_setting($pdo, 'accent_color', '#FF5A1F')) . ';">View order status</a></p>';

    return send_email($order['email'], $order['customer_name'], $title . ' — ' . $order['order_ref'], email_layout($title, $body, $pdo));
}

function email_layout(string $title, string $bodyHtml, PDO $pdo): string {
    $siteName = htmlspecialchars(get_setting($pdo, 'site_name', SITE_NAME));
    $accent = get_setting($pdo, 'accent_color', '#FF5A1F');
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $accent)) { $accent = '#FF5A1F'; }
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
        . '<body style="margin:0;padding:0;background:#F4F1EC;font-family:Arial,Helvetica,sans-serif;color:#17140F;">'
        . '<div style="max-width:560px;margin:0 auto;padding:32px 24px;">'
        . '<div style="font-size:1.3rem;font-weight:700;margin-bottom:24px;color:' . $accent . ';">' . $siteName . '</div>'
        . '<div style="background:#fff;border-radius:16px;padding:28px;border-top:4px solid ' . $accent . ';">'
        . '<h1 style="font-size:1.2rem;margin:0 0 16px;">' . htmlspecialchars($title) . '</h1>'
        . $bodyHtml
        . '</div>'
        . '<p style="color:#837C74;font-size:0.8rem;margin-top:20px;">This is an automated message from ' . $siteName . '.</p>'
        . '</div></body></html>';
}
