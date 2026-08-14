<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['action'] ?? '') === 'mark_read') {
    $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?")->execute([(int) $_POST['id']]);
    redirect(base_url('admin/messages.php'));
}

$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
$newCount = 0;
foreach ($messages as $m) { if ($m['status'] === 'new') { $newCount++; } }

$pageTitle = 'Contact Messages';
require_once __DIR__ . '/includes/header.php';
?>

<div class="account-tabs" style="border-bottom:1px solid var(--line);">
  <a href="<?= base_url('admin/messages.php') ?>" class="active">Contact Inbox<?= $newCount ? ' (' . $newCount . ' new)' : '' ?></a>
  <a href="<?= base_url('admin/subscribers.php') ?>">Newsletter Subscribers</a>
</div>

<div class="admin-card">
  <div class="admin-table-wrap">
    <?php if ($messages): ?>
      <table class="admin-table">
        <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($messages as $m): ?>
            <tr>
              <td><?= htmlspecialchars($m['name']) ?><br><span class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($m['email']) ?></span></td>
              <td><?= htmlspecialchars($m['subject'] ?: '—') ?></td>
              <td style="max-width:340px;white-space:normal;"><?= nl2br(htmlspecialchars($m['message'])) ?></td>
              <td class="text-muted"><?= date('d M Y', strtotime($m['created_at'])) ?></td>
              <td><span class="pill pill-<?= $m['status'] === 'new' ? 'pending' : 'active' ?>"><?= htmlspecialchars($m['status']) ?></span></td>
              <td>
                <?php if ($m['status'] === 'new'): ?>
                  <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="mark_read">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm">Mark read</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="admin-card-body text-muted">No messages yet.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
