<?php
require_once __DIR__ . '/includes/auth.php';

$coupons = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Coupons';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
  <div class="admin-card-head">
    <h3><?= count($coupons) ?> coupon<?= count($coupons) === 1 ? '' : 's' ?></h3>
    <a href="<?= base_url('admin/coupon-form.php') ?>" class="btn btn-primary btn-sm">+ Add Coupon</a>
  </div>
  <div class="admin-table-wrap">
    <?php if ($coupons): ?>
    <table class="admin-table">
      <thead><tr><th>Code</th><th>Discount</th><th>Min. spend</th><th>Uses</th><th>Expires</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($coupons as $c): ?>
          <tr>
            <td><strong><?= htmlspecialchars($c['code']) ?></strong></td>
            <td><?= $c['type'] === 'percent' ? (int) $c['value'] . '%' : format_price((float) $c['value']) ?></td>
            <td><?= $c['min_subtotal'] > 0 ? format_price((float) $c['min_subtotal']) : '&mdash;' ?></td>
            <td><?= (int) $c['used_count'] ?><?= $c['max_uses'] !== null ? ' / ' . (int) $c['max_uses'] : '' ?></td>
            <td class="text-muted"><?= $c['expires_at'] ? date('d M Y', strtotime($c['expires_at'])) : 'Never' ?></td>
            <td><span class="pill pill-<?= $c['status'] === 'active' ? 'active' : 'inactive' ?>"><?= htmlspecialchars($c['status']) ?></span></td>
            <td>
              <div class="admin-actions">
                <a href="<?= base_url('admin/coupon-form.php?id=' . (int) $c['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
                <form action="<?= base_url('admin/delete-coupon.php') ?>" method="post" onsubmit="return confirm('Delete this coupon?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="admin-card-body text-muted">No coupons yet.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
