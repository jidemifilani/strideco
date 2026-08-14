<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session_if_needed();

if (is_customer_logged_in()) {
    redirect(base_url('account/index.php'));
}

$next = $_GET['next'] ?? $_POST['next'] ?? 'account/index.php';
if (strpos(base_url($next), base_url()) !== 0) {
    $next = 'account/index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf() || honeypot_triggered()) {
        set_flash('error', 'Something went wrong, please try again.');
        redirect(base_url('account/login.php'));
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE email = ?');
    $stmt->execute([$email]);
    $customer = $stmt->fetch();

    if ($customer && is_account_locked($customer)) {
        set_flash('error', lockout_message($customer));
        redirect(base_url('account/login.php'));
    }

    if ($customer && password_verify($password, $customer['password_hash'])) {
        reset_login_attempts($pdo, 'customers', $customer['id']);
        session_regenerate_id(true);
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_name'] = $customer['name'];
        sync_wishlist_to_db($pdo, $customer['id']);
        redirect(base_url($next));
    }

    if ($customer) {
        register_failed_login($pdo, 'customers', $customer['id'], (int) $customer['failed_attempts']);
    }
    set_flash('error', 'Invalid email or password.');
    redirect(base_url('account/login.php?next=' . urlencode($next)));
}

$pageTitle = 'Log In';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container" style="max-width:420px;">
    <h1 style="margin-bottom:6px;">Log in</h1>
    <p class="text-muted" style="margin-bottom:28px;">Welcome back to StrideCo.</p>

    <form method="post" action="<?= base_url('account/login.php') ?>" class="checkout-card">
      <?= csrf_field() ?>
      <?= honeypot_field() ?>
      <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log in</button>
      <p class="form-hint" style="text-align:center;margin-top:14px;">
        New here? <a href="<?= base_url('account/register.php') ?>" style="color:var(--accent-dark);font-weight:600;">Create an account</a>
      </p>
    </form>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
