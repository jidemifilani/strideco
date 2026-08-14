<?php
require_once __DIR__ . '/includes/auth.php';
require_super_admin();

$adminsList = $pdo->query('SELECT * FROM admins ORDER BY created_at')->fetchAll();

$pageTitle = 'Admin Users';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
  <div class="admin-card-head">
    <h3><?= count($adminsList) ?> admin user<?= count($adminsList) === 1 ? '' : 's' ?></h3>
    <a href="<?= base_url('admin/admin-form.php') ?>" class="btn btn-primary btn-sm">+ Add Admin</a>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>Username</th><th>Role</th><th>Added</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($adminsList as $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['username']) ?></td>
            <td><span class="pill <?= $a['role'] === 'super_admin' ? 'pill-active' : 'pill-processing' ?>"><?= $a['role'] === 'super_admin' ? 'Super Admin' : 'Staff' ?></span></td>
            <td class="text-muted"><?= date('d M Y', strtotime($a['created_at'])) ?></td>
            <td>
              <?php if ((int) $a['id'] !== (int) $_SESSION['admin_id']): ?>
                <form action="<?= base_url('admin/delete-admin.php') ?>" method="post" onsubmit="return confirm('Remove this admin user?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                </form>
              <?php else: ?>
                <span class="text-muted" style="font-size:0.8rem;">This is you</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
