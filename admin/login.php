<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session_if_needed();

if (!empty($_SESSION['admin_id'])) {
    redirect(base_url('admin/index.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Session expired, please try again.');
        redirect(base_url('admin/login.php'));
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && is_account_locked($admin)) {
        set_flash('error', lockout_message($admin));
        redirect(base_url('admin/login.php'));
    }

    if ($admin && password_verify($password, $admin['password_hash'])) {
        reset_login_attempts($pdo, 'admins', $admin['id']);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        redirect(base_url('admin/index.php'));
    }

    if ($admin) {
        register_failed_login($pdo, 'admins', $admin['id'], (int) $admin['failed_attempts']);
    }
    set_flash('error', 'Invalid username or password.');
    redirect(base_url('admin/login.php'));
}

$adminSiteName = get_setting($pdo, 'site_name', SITE_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login · <?= htmlspecialchars($adminSiteName) ?></title>
<link rel="icon" href="<?= base_url('image.php?icon=1') ?>" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
</head>
<body>
<div class="login-shell">
  <div class="login-card">
    <div class="logo">
      <svg class="logo-mark" viewBox="0 0 48 32" aria-hidden="true">
        <path d="M4 22 C4 22 10 10 20 9 C26 8.4 27 12 32 12 C37 12 38 8 43 8 L44 15 C44 15 40 14 37 16 C34 18 33 22 26 22 Z" fill="currentColor"/>
        <path d="M4 22 L44 22 L44 26 C44 26 38 27 30 27 L8 27 C5 27 4 25 4 22 Z" fill="currentColor" opacity="0.55"/>
      </svg>
      <span class="logo-text"><?= htmlspecialchars($adminSiteName) ?></span>
    </div>
    <h2>Admin Login</h2>
    <p class="sub text-muted">Manage products and orders</p>
    <?php render_flash(); ?>
    <form method="post" action="<?= base_url('admin/login.php') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>
  </div>
</div>
</body>
</html>
