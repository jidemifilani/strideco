<?php
require_once __DIR__ . '/includes/auth.php';

$couponId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $couponId > 0;
$coupon = ['code' => '', 'type' => 'percent', 'value' => '', 'min_subtotal' => '0', 'max_uses' => '', 'expires_at' => '', 'status' => 'active'];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE id = ?');
    $stmt->execute([$couponId]);
    $found = $stmt->fetch();
    if (!$found) {
        set_flash('error', 'Coupon not found.');
        redirect(base_url('admin/coupons.php'));
    }
    $coupon = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $coupon['code'] = strtoupper(trim($_POST['code'] ?? ''));
    $coupon['type'] = ($_POST['type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
    $coupon['value'] = trim($_POST['value'] ?? '');
    $coupon['min_subtotal'] = trim($_POST['min_subtotal'] ?? '0');
    $coupon['max_uses'] = trim($_POST['max_uses'] ?? '');
    $coupon['expires_at'] = trim($_POST['expires_at'] ?? '');
    $coupon['status'] = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($coupon['code'] === '') { $errors[] = 'Code is required.'; }
    if (!is_numeric($coupon['value']) || (float) $coupon['value'] <= 0) { $errors[] = 'Enter a valid discount value.'; }
    if ($coupon['type'] === 'percent' && (float) $coupon['value'] > 100) { $errors[] = 'Percentage discount cannot exceed 100.'; }

    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM coupons WHERE code = ? AND id != ?');
        $check->execute([$coupon['code'], $couponId]);
        if ($check->fetch()) {
            $errors[] = 'That coupon code is already in use.';
        }
    }

    if (!$errors) {
        $maxUses = $coupon['max_uses'] !== '' ? (int) $coupon['max_uses'] : null;
        $expiresAt = $coupon['expires_at'] !== '' ? $coupon['expires_at'] : null;

        if ($isEdit) {
            $pdo->prepare('UPDATE coupons SET code=?, type=?, value=?, min_subtotal=?, max_uses=?, expires_at=?, status=? WHERE id=?')
                ->execute([$coupon['code'], $coupon['type'], $coupon['value'], $coupon['min_subtotal'], $maxUses, $expiresAt, $coupon['status'], $couponId]);
        } else {
            $pdo->prepare('INSERT INTO coupons (code, type, value, min_subtotal, max_uses, expires_at, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$coupon['code'], $coupon['type'], $coupon['value'], $coupon['min_subtotal'], $maxUses, $expiresAt, $coupon['status']]);
        }
        set_flash('success', 'Coupon saved.');
        redirect(base_url('admin/coupons.php'));
    }
}

$pageTitle = $isEdit ? 'Edit Coupon' : 'Add Coupon';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
  <div class="form-errors"><?= implode(' ', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<form method="post" class="admin-card" style="padding:26px;max-width:520px;">
  <?= csrf_field() ?>
  <div class="form-group">
    <label for="code">Coupon code</label>
    <input type="text" id="code" name="code" required value="<?= htmlspecialchars($coupon['code']) ?>" style="text-transform:uppercase;" placeholder="e.g. SAVE20">
  </div>
  <div class="form-row">
    <div class="form-group">
      <label for="type">Discount type</label>
      <select id="type" name="type">
        <option value="percent" <?= $coupon['type'] === 'percent' ? 'selected' : '' ?>>Percentage (%)</option>
        <option value="fixed" <?= $coupon['type'] === 'fixed' ? 'selected' : '' ?>>Fixed amount (&#8358;)</option>
      </select>
    </div>
    <div class="form-group">
      <label for="value">Discount value</label>
      <input type="number" id="value" name="value" step="0.01" min="0" required value="<?= htmlspecialchars((string) $coupon['value']) ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label for="min_subtotal">Minimum order subtotal (&#8358;)</label>
      <input type="number" id="min_subtotal" name="min_subtotal" step="0.01" min="0" value="<?= htmlspecialchars((string) $coupon['min_subtotal']) ?>">
    </div>
    <div class="form-group">
      <label for="max_uses">Max uses (blank = unlimited)</label>
      <input type="number" id="max_uses" name="max_uses" min="1" value="<?= htmlspecialchars((string) $coupon['max_uses']) ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label for="expires_at">Expires on (blank = never)</label>
      <input type="date" id="expires_at" name="expires_at" value="<?= htmlspecialchars($coupon['expires_at'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="active" <?= $coupon['status'] === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $coupon['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
  </div>
  <div style="display:flex;gap:12px;">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Add coupon' ?></button>
    <a href="<?= base_url('admin/coupons.php') ?>" class="btn btn-outline">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
