<?php
require_once __DIR__ . '/includes/auth.php';

$subscribers = $pdo->query('SELECT * FROM newsletter_subscribers ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Newsletter Subscribers';
require_once __DIR__ . '/includes/header.php';
?>

<div class="account-tabs" style="border-bottom:1px solid var(--line);">
  <a href="<?= base_url('admin/messages.php') ?>">Contact Inbox</a>
  <a href="<?= base_url('admin/subscribers.php') ?>" class="active">Newsletter Subscribers (<?= count($subscribers) ?>)</a>
</div>

<div class="admin-card">
  <div class="admin-card-head">
    <h3><?= count($subscribers) ?> subscriber<?= count($subscribers) === 1 ? '' : 's' ?></h3>
    <?php if ($subscribers): ?>
      <a href="<?= base_url('admin/export-subscribers.php') ?>" class="btn btn-outline btn-sm">&#8595; Export CSV</a>
    <?php endif; ?>
  </div>
  <div class="admin-table-wrap">
    <?php if ($subscribers): ?>
      <table class="admin-table">
        <thead><tr><th>Email</th><th>Subscribed</th></tr></thead>
        <tbody>
          <?php foreach ($subscribers as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['email']) ?></td>
              <td class="text-muted"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="admin-card-body text-muted">No subscribers yet.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
