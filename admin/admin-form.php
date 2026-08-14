<?php
require_once __DIR__ . '/includes/auth.php';
require_super_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = ($_POST['role'] ?? 'staff') === 'super_admin' ? 'super_admin' : 'staff';

    if ($username === '' || !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        $errors[] = 'Username must be 3+ characters (letters, numbers, . _ - only).';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
        $check->execute([$username]);
        if ($check->fetch()) {
            $errors[] = 'That username is already taken.';
        }
    }

    if (!$errors) {
        $pdo->prepare('INSERT INTO admins (username, password_hash, role) VALUES (?, ?, ?)')
            ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
        set_flash('success', 'Admin user added.');
        redirect(base_url('admin/admins.php'));
    }
}

$pageTitle = 'Add Admin';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
  <div class="form-errors"><?= implode(' ', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<form method="post" class="admin-card" style="padding:26px;max-width:420px;">
  <?= csrf_field() ?>
  <div class="form-group">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
  </div>
  <div class="form-group">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required minlength="8">
  </div>
  <div class="form-group">
    <label for="role">Role</label>
    <select id="role" name="role">
      <option value="staff">Staff (manage products &amp; orders)</option>
      <option value="super_admin">Super Admin (also manages admin users)</option>
    </select>
  </div>
  <div style="display:flex;gap:12px;">
    <button type="submit" class="btn btn-primary">Add admin</button>
    <a href="<?= base_url('admin/admins.php') ?>" class="btn btn-outline">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
