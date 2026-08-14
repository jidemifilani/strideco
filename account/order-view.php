<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session_if_needed();
require_customer();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND customer_id = ?');
$stmt->execute([$id, current_customer_id()]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect(base_url('account/orders.php'));
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();
$timeline = get_order_timeline($pdo, $id);

$pageTitle = 'Order ' . $order['order_ref'];
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container">
    <div class="breadcrumb"><a href="<?= base_url('account/orders.php') ?>">&larr; My orders</a></div>

    <div class="results-bar">
      <h1>Order <?= htmlspecialchars($order['order_ref']) ?></h1>
      <a href="<?= base_url('invoice.php?ref=' . urlencode($order['order_ref']) . '&email=' . urlencode($order['email'])) ?>" class="btn btn-outline btn-sm" target="_blank">Print invoice</a>
    </div>

    <div class="checkout-layout">
      <div class="checkout-card">
        <h3>Items</h3>
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
        <div class="summary-row total"><span>Total</span><span><?= format_price((float) $order['total']) ?></span></div>
      </div>

      <div class="summary-card">
        <p class="text-muted" style="margin-bottom:6px;">Payment</p>
        <p style="margin-bottom:16px;"><span class="pill pill-<?= htmlspecialchars($order['payment_status']) ?>"><?= htmlspecialchars($order['payment_status']) ?></span></p>
        <p class="text-muted" style="margin-bottom:6px;">Fulfilment status</p>
        <p style="margin-bottom:16px;"><span class="pill pill-<?= htmlspecialchars($order['order_status']) ?>"><?= htmlspecialchars($order['order_status']) ?></span></p>
        <p class="text-muted" style="margin-bottom:6px;">Delivering to</p>
        <p><?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?></p>
        <p class="text-muted" style="margin-top:18px;font-size:0.8rem;">Placed <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></p>
      </div>
    </div>

    <div class="checkout-card" style="margin-top:24px;max-width:520px;">
      <h3>Order status</h3>
      <?= order_timeline_html($timeline, $order['payment_status']) ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
