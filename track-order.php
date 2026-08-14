<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

$order = null;
$notFound = false;
$refInput = trim($_GET['ref'] ?? '');
$emailInput = trim($_GET['email'] ?? '');

if ($refInput !== '' && $emailInput !== '') {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_ref = ? AND LOWER(email) = LOWER(?)');
    $stmt->execute([$refInput, $emailInput]);
    $order = $stmt->fetch();
    $notFound = !$order;
}

$items = [];
$timeline = [];
if ($order) {
    $itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $itemsStmt->execute([$order['id']]);
    $items = $itemsStmt->fetchAll();
    $timeline = get_order_timeline($pdo, (int) $order['id']);
}

$pageTitle = 'Track Your Order';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container" style="max-width:640px;">
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / Track Order</div>
    <h1 style="margin-bottom:8px;">Track your order</h1>
    <p class="text-muted" style="margin-bottom:28px;">Enter your order reference and the email you used at checkout.</p>

    <form method="get" action="<?= base_url('track-order.php') ?>" class="checkout-card" style="margin-bottom:28px;">
      <div class="form-row">
        <div class="form-group">
          <label for="ref">Order reference</label>
          <input type="text" id="ref" name="ref" required value="<?= htmlspecialchars($refInput) ?>" placeholder="e.g. SC260814ABCDE">
        </div>
        <div class="form-group">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" required value="<?= htmlspecialchars($emailInput) ?>">
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Track order</button>
    </form>

    <?php if ($notFound): ?>
      <div class="form-errors">We couldn't find an order matching that reference and email. Double-check both and try again.</div>
    <?php endif; ?>

    <?php if ($order): ?>
      <div id="orderTrackWrap" data-ref="<?= htmlspecialchars($order['order_ref']) ?>" data-email="<?= htmlspecialchars($order['email']) ?>">
        <div class="checkout-card" style="margin-bottom:20px;">
          <div class="results-bar" style="margin-bottom:0;">
            <h3 style="margin:0;">Order <?= htmlspecialchars($order['order_ref']) ?></h3>
            <a href="<?= base_url('invoice.php?ref=' . urlencode($order['order_ref']) . '&email=' . urlencode($order['email'])) ?>" class="btn btn-outline btn-sm" target="_blank">Print invoice</a>
          </div>
          <div id="orderStatusPills" style="display:flex;gap:10px;margin:16px 0;">
            <span class="pill pill-<?= htmlspecialchars($order['payment_status']) ?>">Payment: <?= htmlspecialchars($order['payment_status']) ?></span>
            <span class="pill pill-<?= htmlspecialchars($order['order_status']) ?>">Status: <?= htmlspecialchars($order['order_status']) ?></span>
          </div>
          <?php foreach ($items as $item): ?>
            <div class="mini-cart-item">
              <div>
                <div class="name"><?= htmlspecialchars($item['product_name']) ?></div>
                <div class="meta"><?= !empty($item['variant_color']) ? htmlspecialchars($item['variant_color']) . ' &middot; ' : '' ?>Size <?= htmlspecialchars($item['size']) ?> &times; <?= (int) $item['quantity'] ?></div>
              </div>
              <div class="price"><?= format_price((float) $item['price'] * (int) $item['quantity']) ?></div>
            </div>
          <?php endforeach; ?>
          <div class="summary-row total" style="margin-top:14px;"><span>Total</span><span><?= format_price((float) $order['total']) ?></span></div>
          <p class="form-hint" style="margin-top:16px;">Delivering to <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?></p>
          <p class="text-muted" style="font-size:0.8rem;margin-top:10px;">Placed <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></p>
        </div>

        <div class="checkout-card">
          <div class="results-bar" style="margin-bottom:18px;">
            <h3 style="margin:0;">Order status</h3>
            <span class="live-indicator"><span class="live-dot"></span> Live &mdash; updates automatically</span>
          </div>
          <div id="orderTimeline"><?= order_timeline_html($timeline, $order['payment_status']) ?></div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
