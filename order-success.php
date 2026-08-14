<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

$ref = trim($_GET['ref'] ?? '');
$stmt = $pdo->prepare('SELECT * FROM orders WHERE order_ref = ? AND payment_status = "paid"');
$stmt->execute([$ref]);
$order = $stmt->fetch();

if (!$order) {
    redirect(base_url('index.php'));
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$order['id']]);
$items = $itemsStmt->fetchAll();

$pageTitle = 'Order confirmed';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container result-page">
    <div class="result-icon success">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <h1>Thank you, <?= htmlspecialchars(explode(' ', $order['customer_name'])[0]) ?>!</h1>
    <p>Your order has been placed and payment was successful. A confirmation has been recorded for <?= htmlspecialchars($order['email']) ?>.</p>
    <div class="order-ref-box"><?= htmlspecialchars($order['order_ref']) ?></div>

    <div class="checkout-card" style="text-align:left;">
      <h3>Order details</h3>
      <?php foreach ($items as $item): ?>
        <div class="mini-cart-item">
          <div>
            <div class="name"><?= htmlspecialchars($item['product_name']) ?></div>
            <div class="meta"><?= !empty($item['variant_color']) ? htmlspecialchars($item['variant_color']) . ' &middot; ' : '' ?>Size <?= htmlspecialchars($item['size']) ?> &times; <?= (int) $item['quantity'] ?></div>
          </div>
          <div class="price"><?= format_price((float) $item['price'] * (int) $item['quantity']) ?></div>
        </div>
      <?php endforeach; ?>
      <div class="summary-row" style="margin-top:14px;"><span>Subtotal</span><span><?= format_price((float) $order['subtotal']) ?></span></div>
      <?php if ($order['discount_amount'] > 0): ?>
        <div class="summary-row"><span>Discount<?= $order['coupon_code'] ? ' (' . htmlspecialchars($order['coupon_code']) . ')' : '' ?></span><span>&minus;<?= format_price((float) $order['discount_amount']) ?></span></div>
      <?php endif; ?>
      <div class="summary-row"><span>Shipping</span><span><?= $order['shipping_fee'] > 0 ? format_price((float) $order['shipping_fee']) : 'Free' ?></span></div>
      <?php if ($order['tax_amount'] > 0): ?>
        <div class="summary-row"><span>Tax</span><span><?= format_price((float) $order['tax_amount']) ?></span></div>
      <?php endif; ?>
      <div class="summary-row total"><span>Total paid</span><span><?= format_price((float) $order['total']) ?></span></div>
      <p class="form-hint" style="margin-top:16px;">Delivering to: <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?></p>
    </div>

    <a href="<?= base_url('shop.php') ?>" class="btn btn-primary" style="margin-top:28px;">Continue shopping</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
