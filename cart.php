<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

if (!empty($_GET['restore'])) {
    // Only restore into an empty cart — never clobber items someone's
    // actively assembled in this session.
    if (empty(get_cart_raw())) {
        $abandoned = get_abandoned_cart_by_token($pdo, $_GET['restore']);
        if ($abandoned) {
            $snapshot = json_decode($abandoned['cart_snapshot'], true) ?: [];
            $restoredCount = 0;
            foreach ($snapshot as $item) {
                $productCheck = $pdo->prepare('SELECT is_preorder FROM products WHERE id = ?');
                $productCheck->execute([$item['product_id']]);
                $isPreorderItem = (bool) $productCheck->fetchColumn();

                if ($isPreorderItem) {
                    add_to_cart((int) $item['product_id'], $item['size'], (int) $item['qty'], $item['variant_id'] ? (int) $item['variant_id'] : null);
                    $restoredCount++;
                    continue;
                }

                if (!empty($item['variant_id'])) {
                    $stockStmt = $pdo->prepare('SELECT stock FROM variant_sizes WHERE variant_id = ? AND size = ?');
                    $stockStmt->execute([$item['variant_id'], $item['size']]);
                } else {
                    $stockStmt = $pdo->prepare('SELECT stock FROM product_sizes WHERE product_id = ? AND size = ?');
                    $stockStmt->execute([$item['product_id'], $item['size']]);
                }
                $stock = (int) ($stockStmt->fetchColumn() ?: 0);
                if ($stock > 0) {
                    add_to_cart((int) $item['product_id'], $item['size'], min((int) $item['qty'], $stock), $item['variant_id'] ? (int) $item['variant_id'] : null);
                    $restoredCount++;
                }
            }
            mark_abandoned_cart_recovered($pdo, $abandoned['email']);
            set_flash($restoredCount > 0 ? 'success' : 'error', $restoredCount > 0 ? 'Welcome back! We restored your cart.' : 'Sorry, those items are no longer available.');
        } else {
            set_flash('error', 'That cart recovery link has expired or already been used.');
        }
    }
    redirect(base_url('cart.php'));
}

$pageTitle = 'Your Cart';
require_once __DIR__ . '/includes/header.php';

$items = get_cart_details($pdo);
$subtotal = 0;
foreach ($items as $line) { $subtotal += $line['line_total']; }
$shipping = $subtotal > 0 ? SHIPPING_FEE : 0;
if ($subtotal >= FREE_SHIPPING_THRESHOLD) { $shipping = 0; }
$total = $subtotal + $shipping;
$cartPageUrl = base_url('cart.php');
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container">
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / Cart</div>
    <h1 style="margin-bottom:28px;">Your Cart</h1>

    <?php if (!$items): ?>
      <div class="empty-state">
        <h3>Your cart is empty</h3>
        <p>Looks like you haven't added any shoes yet.</p>
        <a href="<?= base_url('shop.php') ?>" class="btn btn-primary btn-sm">Start shopping</a>
      </div>
    <?php else: ?>
      <div class="cart-layout">
        <table class="cart-table">
          <thead>
            <tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($items as $line): $p = $line['product']; $v = $line['variant']; ?>
              <tr>
                <td>
                  <div class="cart-item">
                    <img src="<?= $v ? variant_image_url($v, $p) : product_image_url($p) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    <div>
                      <div class="cart-item-name"><a href="<?= base_url('product.php?slug=' . urlencode($p['slug'])) ?>"><?= htmlspecialchars($p['name']) ?></a><?php if (!empty($p['is_preorder'])): ?> <span class="preorder-tag">Pre-order</span><?php endif; ?></div>
                      <div class="cart-item-meta"><?= $v ? htmlspecialchars($v['color_name']) . ' &middot; ' : '' ?>Size: <?= htmlspecialchars($line['size']) ?></div>
                    </div>
                  </div>
                </td>
                <td><?= format_price((float) $p['price']) ?></td>
                <td>
                  <form class="cart-qty-form" action="<?= base_url('cart-actions.php') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="key" value="<?= htmlspecialchars($line['key']) ?>">
                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($cartPageUrl) ?>">
                    <input type="number" name="qty" value="<?= (int) $line['qty'] ?>" min="1" max="10" style="width:64px;padding:8px;">
                  </form>
                </td>
                <td><?= format_price((float) $line['line_total']) ?></td>
                <td>
                  <form action="<?= base_url('cart-actions.php') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="key" value="<?= htmlspecialchars($line['key']) ?>">
                    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($cartPageUrl) ?>">
                    <button type="submit" class="remove-link" style="background:none;border:none;padding:0;">Remove</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="summary-card">
          <h3>Order Summary</h3>
          <div class="summary-row"><span>Subtotal</span><span><?= format_price($subtotal) ?></span></div>
          <div class="summary-row"><span>Shipping</span><span><?= $shipping > 0 ? format_price($shipping) : 'Free' ?></span></div>
          <div class="summary-row total"><span>Total</span><span><?= format_price($total) ?></span></div>
          <?= shipping_nudge_html($subtotal) ?>
          <a href="<?= base_url('checkout.php') ?>" class="btn btn-primary btn-block" style="margin-top:18px;">Proceed to Checkout</a>
          <div class="trust-badges">
            <span>&#10003; Secure checkout via Paystack</span>
            <span>&#10003; Free shipping over <?= format_price(FREE_SHIPPING_THRESHOLD) ?></span>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
