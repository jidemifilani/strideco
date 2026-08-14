<?php
require_once __DIR__ . '/includes/auth.php';

$carts = $pdo->query(
    "SELECT * FROM abandoned_carts WHERE recovered_at IS NULL ORDER BY updated_at DESC LIMIT 100"
)->fetchAll();

$pageTitle = 'Abandoned Carts';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
  <div class="admin-card-head">
    <h3><?= count($carts) ?> unrecovered cart<?= count($carts) === 1 ? '' : 's' ?></h3>
  </div>
  <div class="admin-table-wrap">
    <?php if ($carts): ?>
    <table class="admin-table">
      <thead><tr><th>Email</th><th>Items</th><th>Subtotal</th><th>Last seen</th><th>Reminder sent</th></tr></thead>
      <tbody>
        <?php foreach ($carts as $c): $items = json_decode($c['cart_snapshot'], true) ?: []; ?>
          <tr>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td class="text-muted"><?= implode(', ', array_map(fn($i) => htmlspecialchars($i['name']) . ' (' . htmlspecialchars($i['size']) . ')', $items)) ?></td>
            <td><?= format_price((float) $c['subtotal']) ?></td>
            <td class="text-muted"><?= date('d M Y, H:i', strtotime($c['updated_at'])) ?></td>
            <td><?= $c['reminder_sent_at'] ? '<span class="pill pill-active">Sent</span>' : '<span class="pill pill-pending">Not yet</span>' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="admin-card-body text-muted">No unrecovered abandoned carts right now.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
