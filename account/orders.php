<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session_if_needed();
require_customer();

$customerId = current_customer_id();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
$stmt->execute([$customerId]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container">
    <h1 style="margin-bottom:20px;">My Account</h1>
    <nav class="account-tabs">
      <a href="<?= base_url('account/index.php') ?>">Profile</a>
      <a href="<?= base_url('account/orders.php') ?>" class="active">Orders (<?= count($orders) ?>)</a>
      <a href="<?= base_url('wishlist.php') ?>">Wishlist</a>
      <a href="<?= base_url('account/logout.php') ?>">Log out</a>
    </nav>

    <?php if (!$orders): ?>
      <div class="empty-state">
        <h3>No orders yet</h3>
        <p>When you place an order, it'll show up here.</p>
        <a href="<?= base_url('shop.php') ?>" class="btn btn-primary btn-sm">Start shopping</a>
      </div>
    <?php else: ?>
      <div class="admin-card">
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>Reference</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
                <tr>
                  <td><?= htmlspecialchars($order['order_ref']) ?></td>
                  <td><?= format_price((float) $order['total']) ?></td>
                  <td><span class="pill pill-<?= htmlspecialchars($order['payment_status']) ?>"><?= htmlspecialchars($order['payment_status']) ?></span></td>
                  <td><span class="pill pill-<?= htmlspecialchars($order['order_status']) ?>"><?= htmlspecialchars($order['order_status']) ?></span></td>
                  <td class="text-muted"><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                  <td><a href="<?= base_url('account/order-view.php?id=' . (int) $order['id']) ?>" class="btn btn-outline btn-sm">View</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
