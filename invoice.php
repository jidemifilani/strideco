<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

$ref = trim($_GET['ref'] ?? '');
$email = trim($_GET['email'] ?? '');

$stmt = $pdo->prepare('SELECT * FROM orders WHERE order_ref = ?');
$stmt->execute([$ref]);
$order = $stmt->fetch();

$authorized = false;
if ($order) {
    if (is_customer_logged_in() && $order['customer_id'] !== null && (int) $order['customer_id'] === current_customer_id()) {
        $authorized = true;
    } elseif ($email !== '' && strcasecmp($order['email'], $email) === 0) {
        $authorized = true;
    }
}

if (!$order || !$authorized) {
    http_response_code(404);
    echo 'Invoice not found.';
    exit;
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$order['id']]);
$items = $itemsStmt->fetchAll();

$invSiteName = get_setting($pdo, 'site_name', SITE_NAME);
$invAccentColor = get_setting($pdo, 'accent_color', '#FF5A1F');
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $invAccentColor)) { $invAccentColor = '#FF5A1F'; }
$invEmail = get_setting($pdo, 'contact_email', 'hello@strideco.example');
$invAddress = get_setting($pdo, 'contact_address', 'Lagos, Nigeria');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice <?= htmlspecialchars($order['order_ref']) ?> · <?= htmlspecialchars($invSiteName) ?></title>
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; color: #17140F; max-width: 720px; margin: 40px auto; padding: 0 24px; }
  .inv-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #17140F; padding-bottom: 20px; margin-bottom: 24px; }
  .inv-head h1 { font-size: 1.5rem; margin: 0 0 4px; }
  .brand { font-size: 1.3rem; font-weight: 700; }
  .brand span { color: <?= $invAccentColor ?>; }
  .meta { text-align: right; font-size: 0.9rem; color: #4A4540; }
  .cols { display: flex; justify-content: space-between; margin-bottom: 28px; gap: 24px; }
  .cols h4 { margin: 0 0 6px; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; color: #837C74; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  th { text-align: left; font-size: 0.75rem; text-transform: uppercase; color: #837C74; border-bottom: 1px solid #E8E4DE; padding: 8px 4px; }
  td { padding: 10px 4px; border-bottom: 1px solid #E8E4DE; font-size: 0.92rem; }
  .totals { width: 280px; margin-left: auto; }
  .totals div { display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.92rem; }
  .totals .grand { font-weight: 700; font-size: 1.1rem; border-top: 2px solid #17140F; padding-top: 10px; margin-top: 4px; }
  .print-btn { margin-top: 30px; padding: 10px 22px; background: <?= $invAccentColor ?>; color: #fff; border: none; border-radius: 999px; font-weight: 600; cursor: pointer; }
  @media print { .print-btn { display: none; } body { margin: 0; } }
</style>
</head>
<body>
  <div class="inv-head">
    <div>
      <div class="brand" style="color:<?= $invAccentColor ?>;"><?= htmlspecialchars($invSiteName) ?></div>
      <p style="margin:4px 0 0;color:#837C74;font-size:0.85rem;"><?= htmlspecialchars($invEmail) ?> &middot; <?= htmlspecialchars($invAddress) ?></p>
    </div>
    <div class="meta">
      <h1>Invoice</h1>
      <div><?= htmlspecialchars($order['order_ref']) ?></div>
      <div><?= date('d M Y', strtotime($order['created_at'])) ?></div>
    </div>
  </div>

  <div class="cols">
    <div>
      <h4>Billed to</h4>
      <div><?= htmlspecialchars($order['customer_name']) ?></div>
      <div><?= htmlspecialchars($order['email']) ?></div>
      <div><?= htmlspecialchars($order['phone']) ?></div>
    </div>
    <div>
      <h4>Delivery address</h4>
      <div><?= htmlspecialchars($order['address']) ?></div>
      <div><?= htmlspecialchars($order['city']) ?></div>
    </div>
    <div>
      <h4>Payment</h4>
      <div style="text-transform:capitalize;"><?= htmlspecialchars($order['payment_status']) ?></div>
      <?php if ($order['payment_reference']): ?><div style="font-size:0.8rem;color:#837C74;"><?= htmlspecialchars($order['payment_reference']) ?></div><?php endif; ?>
    </div>
  </div>

  <table>
    <thead><tr><th>Item</th><th>Size</th><th>Qty</th><th>Price</th><th style="text-align:right;">Subtotal</th></tr></thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= htmlspecialchars($item['product_name']) ?><?php if (!empty($item['variant_color'])): ?> <span style="color:#837C74;">(<?= htmlspecialchars($item['variant_color']) ?>)</span><?php endif; ?><?php if (!empty($item['is_preorder'])): ?> <span style="color:#1A56C4;font-weight:700;font-size:0.7rem;text-transform:uppercase;">[Pre-order<?= $item['preorder_available_at'] ? ' — ' . date('d M Y', strtotime($item['preorder_available_at'])) : '' ?>]</span><?php endif; ?></td>
          <td><?= htmlspecialchars($item['size']) ?></td>
          <td><?= (int) $item['quantity'] ?></td>
          <td><?= format_price((float) $item['price']) ?></td>
          <td style="text-align:right;"><?= format_price((float) $item['price'] * (int) $item['quantity']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="totals">
    <div><span>Subtotal</span><span><?= format_price((float) $order['subtotal']) ?></span></div>
    <?php if ($order['discount_amount'] > 0): ?>
      <div><span>Discount<?= $order['coupon_code'] ? ' (' . htmlspecialchars($order['coupon_code']) . ')' : '' ?></span><span>&minus;<?= format_price((float) $order['discount_amount']) ?></span></div>
    <?php endif; ?>
    <div><span>Shipping</span><span><?= $order['shipping_fee'] > 0 ? format_price((float) $order['shipping_fee']) : 'Free' ?></span></div>
    <?php if ($order['tax_amount'] > 0): ?>
      <div><span>Tax</span><span><?= format_price((float) $order['tax_amount']) ?></span></div>
    <?php endif; ?>
    <div class="grand"><span>Total</span><span><?= format_price((float) $order['total']) ?></span></div>
  </div>

  <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
</body>
</html>
