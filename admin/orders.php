<?php
require_once __DIR__ . '/includes/auth.php';

$statusFilter = $_GET['status'] ?? '';
$validStatuses = ['processing', 'shipped', 'delivered', 'cancelled'];

$sql = 'SELECT * FROM orders';
$params = [];
if (in_array($statusFilter, $validStatuses, true)) {
    $sql .= ' WHERE order_status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$counts = ['' => 0];
foreach ($validStatuses as $s) { $counts[$s] = 0; }
foreach ($pdo->query('SELECT order_status, COUNT(*) AS c FROM orders GROUP BY order_status') as $row) {
    $counts[$row['order_status']] = (int) $row['c'];
    $counts[''] += (int) $row['c'];
}

$pageTitle = 'Orders';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
  <div class="admin-card-head" style="gap:8px;flex-wrap:wrap;">
    <div class="admin-actions" style="flex-wrap:wrap;">
      <a href="<?= base_url('admin/orders.php') ?>" class="btn <?= $statusFilter === '' ? 'btn-dark' : 'btn-outline' ?> btn-sm">All (<?= $counts[''] ?>)</a>
      <?php foreach ($validStatuses as $s): ?>
        <a href="<?= base_url('admin/orders.php?status=' . $s) ?>" class="btn <?= $statusFilter === $s ? 'btn-dark' : 'btn-outline' ?> btn-sm"><?= ucfirst($s) ?> (<?= $counts[$s] ?>)</a>
      <?php endforeach; ?>
    </div>
    <a href="<?= base_url('admin/export-orders.php') ?>" class="btn btn-outline btn-sm">&#8595; Export CSV</a>
  </div>
  <div class="admin-table-wrap">
    <?php if ($orders): ?>
    <table class="admin-table">
      <thead><tr><th>Reference</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
          <tr>
            <td><?= htmlspecialchars($order['order_ref']) ?></td>
            <td><?= htmlspecialchars($order['customer_name']) ?><br><span class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($order['email']) ?></span></td>
            <td><?= format_price((float) $order['total']) ?></td>
            <td><span class="pill pill-<?= htmlspecialchars($order['payment_status']) ?>"><?= htmlspecialchars($order['payment_status']) ?></span></td>
            <td><span class="pill pill-<?= htmlspecialchars($order['order_status']) ?>"><?= htmlspecialchars($order['order_status']) ?></span></td>
            <td class="text-muted"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></td>
            <td><a href="<?= base_url('admin/order-view.php?id=' . (int) $order['id']) ?>" class="btn btn-outline btn-sm">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="admin-card-body text-muted">No orders found.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
