<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../paystack/paystack-client.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect(base_url('admin/orders.php'));
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();
$timeline = get_order_timeline($pdo, $id);

$pageTitle = 'Order ' . $order['order_ref'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb" style="margin-bottom:18px;"><a href="<?= base_url('admin/orders.php') ?>">&larr; All orders</a></div>

<div class="checkout-layout">
  <div>
    <div class="admin-card">
      <div class="admin-card-head"><h3>Items</h3></div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Product</th><th>Size</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?= htmlspecialchars($item['product_name']) ?><?php if (!empty($item['variant_color'])): ?> <span class="text-muted">(<?= htmlspecialchars($item['variant_color']) ?>)</span><?php endif; ?><?php if (!empty($item['is_preorder'])): ?> <span class="preorder-tag">Pre-order</span><?php endif; ?></td>
                <td><?= htmlspecialchars($item['size']) ?></td>
                <td><?= (int) $item['quantity'] ?></td>
                <td><?= format_price((float) $item['price']) ?></td>
                <td><?= format_price((float) $item['price'] * (int) $item['quantity']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="admin-card-body">
        <div class="summary-row"><span>Subtotal</span><span><?= format_price((float) $order['subtotal']) ?></span></div>
        <?php if ($order['discount_amount'] > 0): ?>
          <div class="summary-row"><span>Discount<?= $order['coupon_code'] ? ' (' . htmlspecialchars($order['coupon_code']) . ')' : '' ?></span><span>&minus;<?= format_price((float) $order['discount_amount']) ?></span></div>
        <?php endif; ?>
        <div class="summary-row"><span>Shipping</span><span><?= $order['shipping_fee'] > 0 ? format_price((float) $order['shipping_fee']) : 'Free' ?></span></div>
        <?php if ($order['tax_amount'] > 0): ?>
          <div class="summary-row"><span>Tax</span><span><?= format_price((float) $order['tax_amount']) ?></span></div>
        <?php endif; ?>
        <div class="summary-row total"><span>Total</span><span><?= format_price((float) $order['total']) ?></span></div>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h3>Customer &amp; delivery</h3></div>
      <div class="admin-card-body">
        <p><strong><?= htmlspecialchars($order['customer_name']) ?></strong></p>
        <p class="text-muted"><?= htmlspecialchars($order['email']) ?> &middot; <?= htmlspecialchars($order['phone']) ?></p>
        <p><?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?></p>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h3>Order timeline</h3></div>
      <div class="admin-card-body">
        <?= order_timeline_html($timeline, $order['payment_status']) ?>
      </div>
    </div>
  </div>

  <div class="summary-card">
    <h3>Order status</h3>
    <p class="text-muted" style="margin-bottom:4px;">Reference</p>
    <p style="font-weight:700;margin-bottom:16px;"><?= htmlspecialchars($order['order_ref']) ?></p>

    <p class="text-muted" style="margin-bottom:6px;">Payment</p>
    <p style="margin-bottom:16px;"><span class="pill pill-<?= htmlspecialchars($order['payment_status']) ?>"><?= htmlspecialchars($order['payment_status']) ?></span>
      <?php if (!empty($order['payment_reference'])): ?><br><span class="text-muted" style="font-size:0.78rem;">Ref: <?= htmlspecialchars($order['payment_reference']) ?></span><?php endif; ?>
    </p>

    <?php if ($order['payment_status'] === 'paid'): ?>
      <details style="margin-bottom:16px;">
        <summary style="cursor:pointer;font-weight:600;font-size:0.88rem;color:var(--danger);">Refund this order</summary>
        <form action="<?= base_url('admin/refund-order.php') ?>" method="post" style="margin-top:10px;" onsubmit="return confirm('Refund <?= htmlspecialchars(format_price((float) $order['total'])) ?> for this order? This cannot be undone.');">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
          <div class="form-group">
            <label for="reason">Reason (optional)</label>
            <input type="text" id="reason" name="reason" placeholder="e.g. Customer returned item" maxlength="255">
            <p class="form-hint">Refunds the full <?= format_price((float) $order['total']) ?><?= paystack_configured() ? ' via Paystack.' : ' (recorded locally — Paystack test keys are not configured).' ?></p>
          </div>
          <button type="submit" class="btn btn-danger btn-block btn-sm">Process refund</button>
        </form>
      </details>
    <?php elseif ($order['payment_status'] === 'refunded'): ?>
      <p class="text-muted" style="margin-bottom:16px;font-size:0.82rem;">
        Refunded <?= format_price((float) $order['refund_amount']) ?> on <?= date('d M Y', strtotime($order['refunded_at'])) ?>
        <?php if (!empty($order['refund_reference'])): ?><br>Ref: <?= htmlspecialchars($order['refund_reference']) ?><?php endif; ?>
      </p>
    <?php endif; ?>

    <form action="<?= base_url('admin/update-order-status.php') ?>" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
      <div class="form-group">
        <label for="order_status">Fulfilment status</label>
        <select id="order_status" name="order_status">
          <?php foreach (['processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $order['order_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="note">Note (optional)</label>
        <input type="text" id="note" name="note" placeholder="e.g. Shipped via GIG Logistics, tracking #ABC123" maxlength="255">
        <p class="form-hint">Shown to the customer on their order timeline.</p>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Update status</button>
    </form>

    <p class="text-muted" style="margin-top:18px;font-size:0.8rem;">Placed <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></p>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
