<?php
require_once __DIR__ . '/includes/auth.php';

$statusFilter = $_GET['status'] ?? 'pending';
$validStatuses = ['pending', 'approved', 'rejected'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = 'pending';
}

$stmt = $pdo->prepare(
    "SELECT r.*, p.name AS product_name, p.slug AS product_slug
     FROM reviews r JOIN products p ON p.id = r.product_id
     WHERE r.status = ? ORDER BY r.created_at DESC"
);
$stmt->execute([$statusFilter]);
$reviews = $stmt->fetchAll();

$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS c FROM reviews GROUP BY status') as $row) {
    $counts[$row['status']] = (int) $row['c'];
}

$pageTitle = 'Reviews';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
  <div class="admin-card-head" style="flex-wrap:wrap;">
    <div class="admin-actions" style="flex-wrap:wrap;">
      <?php foreach ($validStatuses as $s): ?>
        <a href="<?= base_url('admin/reviews.php?status=' . $s) ?>" class="btn <?= $statusFilter === $s ? 'btn-dark' : 'btn-outline' ?> btn-sm"><?= ucfirst($s) ?> (<?= $counts[$s] ?>)</a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="admin-table-wrap">
    <?php if ($reviews): ?>
      <table class="admin-table">
        <thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th>Comment</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($reviews as $r): ?>
            <tr>
              <td><a href="<?= base_url('product.php?slug=' . urlencode($r['product_slug'])) ?>" target="_blank"><?= htmlspecialchars($r['product_name']) ?></a></td>
              <td><?= htmlspecialchars($r['customer_name']) ?></td>
              <td><?= star_rating_html((float) $r['rating'], 13) ?></td>
              <td style="max-width:320px;"><?= htmlspecialchars($r['comment']) ?></td>
              <td class="text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
              <td>
                <div class="admin-actions">
                  <?php if ($statusFilter !== 'approved'): ?>
                    <form action="<?= base_url('admin/moderate-review.php') ?>" method="post">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                      <input type="hidden" name="status" value="approved">
                      <button type="submit" class="btn btn-sm" style="background:var(--success-tint);color:var(--success);">Approve</button>
                    </form>
                  <?php endif; ?>
                  <?php if ($statusFilter !== 'rejected'): ?>
                    <form action="<?= base_url('admin/moderate-review.php') ?>" method="post">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                      <input type="hidden" name="status" value="rejected">
                      <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="admin-card-body text-muted">No <?= $statusFilter ?> reviews.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
