<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
start_session_if_needed();
require_customer();

$customerId = current_customer_id();
$stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    redirect(base_url('account/logout.php'));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');

        if ($name === '') { $errors[] = 'Name is required.'; }

        if (!$errors) {
            $pdo->prepare('UPDATE customers SET name = ?, phone = ?, address = ?, city = ? WHERE id = ?')
                ->execute([$name, $phone, $address, $city, $customerId]);
            $_SESSION['customer_name'] = $name;
            set_flash('success', 'Profile updated.');
            redirect(base_url('account/index.php'));
        }
    } elseif ($formType === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $customer['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        }

        if (!$errors) {
            $pdo->prepare('UPDATE customers SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $customerId]);
            set_flash('success', 'Password changed.');
            redirect(base_url('account/index.php'));
        }
    }
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE customer_id = ?');
$stmt->execute([$customerId]);
$orderCount = (int) $stmt->fetchColumn();

$pageTitle = 'My Account';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container">
    <h1 style="margin-bottom:20px;">My Account</h1>
    <nav class="account-tabs">
      <a href="<?= base_url('account/index.php') ?>" class="active">Profile</a>
      <a href="<?= base_url('account/orders.php') ?>">Orders (<?= $orderCount ?>)</a>
      <a href="<?= base_url('wishlist.php') ?>">Wishlist</a>
      <a href="<?= base_url('account/logout.php') ?>">Log out</a>
    </nav>

    <?php if ($errors): ?>
      <div class="form-errors"><?= implode(' ', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <div class="checkout-layout">
      <div class="checkout-card">
        <h3>Profile &amp; delivery details</h3>
        <p class="form-hint" style="margin-bottom:18px;">Saved here to speed up checkout next time.</p>
        <form method="post" action="<?= base_url('account/index.php') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="form_type" value="profile">
          <div class="form-row">
            <div class="form-group">
              <label for="name">Full name</label>
              <input type="text" id="name" name="name" required value="<?= htmlspecialchars($customer['name']) ?>">
            </div>
            <div class="form-group">
              <label for="phone">Phone number</label>
              <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Email address</label>
            <input type="email" value="<?= htmlspecialchars($customer['email']) ?>" disabled>
          </div>
          <div class="form-group">
            <label for="address">Delivery address</label>
            <textarea id="address" name="address"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label for="city">City</label>
            <input type="text" id="city" name="city" value="<?= htmlspecialchars($customer['city'] ?? '') ?>">
          </div>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </form>
      </div>

      <div class="checkout-card">
        <h3>Change password</h3>
        <form method="post" action="<?= base_url('account/index.php') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="form_type" value="password">
          <div class="form-group">
            <label for="current_password">Current password</label>
            <input type="password" id="current_password" name="current_password" required>
          </div>
          <div class="form-group">
            <label for="new_password">New password</label>
            <input type="password" id="new_password" name="new_password" required minlength="8">
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm new password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
          </div>
          <button type="submit" class="btn btn-outline">Update password</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
