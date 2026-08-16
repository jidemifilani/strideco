<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session_if_needed();

if (is_customer_logged_in()) {
    redirect(base_url('account/index.php'));
}

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf() || honeypot_triggered()) {
        set_flash('error', 'Something went wrong, please try again.');
        redirect(base_url('account/register.php'));
    }

    $old['name'] = trim($_POST['name'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($old['name'] === '') { $errors[] = 'Name is required.'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid email is required.'; }
    if (strlen($password) < 8) { $errors[] = 'Password must be at least 8 characters.'; }
    if ($password !== $confirm) { $errors[] = 'Passwords do not match.'; }

    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM customers WHERE email = ?');
        $check->execute([$old['email']]);
        if ($check->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO customers (name, email, phone, password_hash) VALUES (?, ?, ?, ?)');
        $stmt->execute([$old['name'], $old['email'], $old['phone'], password_hash($password, PASSWORD_DEFAULT)]);
        $customerId = (int) $pdo->lastInsertId();

        session_regenerate_id(true);
        $_SESSION['customer_id'] = $customerId;
        $_SESSION['customer_name'] = $old['name'];
        sync_wishlist_to_db($pdo, $customerId);

        set_flash('success', 'Welcome to Expandable Collection, ' . explode(' ', $old['name'])[0] . '!');
        redirect(base_url('account/index.php'));
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container" style="max-width:460px;">
    <h1 style="margin-bottom:6px;">Create your account</h1>
    <p class="text-muted" style="margin-bottom:28px;">Track orders, save your wishlist, and checkout faster.</p>

    <?php if ($errors): ?>
      <div class="form-errors"><?= implode(' ', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('account/register.php') ?>" class="checkout-card">
      <?= csrf_field() ?>
      <?= honeypot_field() ?>
      <div class="form-group">
        <label for="name">Full name</label>
        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($old['name']) ?>">
      </div>
      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($old['email']) ?>">
      </div>
      <div class="form-group">
        <label for="phone">Phone number</label>
        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($old['phone']) ?>">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required minlength="8">
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm password</label>
          <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create account</button>
      <p class="form-hint" style="text-align:center;margin-top:14px;">
        Already have an account? <a href="<?= base_url('account/login.php') ?>" style="color:var(--accent-dark);font-weight:600;">Log in</a>
      </p>
    </form>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
