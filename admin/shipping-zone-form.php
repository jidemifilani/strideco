<?php
require_once __DIR__ . '/includes/auth.php';

$zoneId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $zoneId > 0;
$zone = ['name' => '', 'states' => '', 'fee' => '', 'is_default' => 0];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM shipping_zones WHERE id = ?');
    $stmt->execute([$zoneId]);
    $found = $stmt->fetch();
    if (!$found) {
        set_flash('error', 'Shipping zone not found.');
        redirect(base_url('admin/shipping-zones.php'));
    }
    $zone = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $zone['name'] = trim($_POST['name'] ?? '');
    $zone['states'] = trim($_POST['states'] ?? '');
    $zone['fee'] = trim($_POST['fee'] ?? '');
    $zone['is_default'] = !empty($_POST['is_default']) ? 1 : 0;

    if ($zone['name'] === '') { $errors[] = 'Zone name is required.'; }
    if (!is_numeric($zone['fee']) || (float) $zone['fee'] < 0) { $errors[] = 'Enter a valid delivery fee.'; }
    if (!$zone['is_default'] && $zone['states'] === '') {
        $errors[] = 'Enter at least one city/state to match, or mark this the default (catch-all) zone.';
    }

    if (!$errors) {
        if ($zone['is_default']) {
            // Only one zone can be the catch-all default.
            $pdo->prepare('UPDATE shipping_zones SET is_default = 0 WHERE id != ?')->execute([$zoneId]);
        }

        if ($isEdit) {
            $pdo->prepare('UPDATE shipping_zones SET name=?, states=?, fee=?, is_default=? WHERE id=?')
                ->execute([$zone['name'], $zone['states'] ?: null, $zone['fee'], $zone['is_default'], $zoneId]);
        } else {
            $pdo->prepare('INSERT INTO shipping_zones (name, states, fee, is_default) VALUES (?, ?, ?, ?)')
                ->execute([$zone['name'], $zone['states'] ?: null, $zone['fee'], $zone['is_default']]);
        }

        // Guarantee exactly one default zone always exists, so checkout never
        // silently falls back to the SHIPPING_FEE constant unexpectedly.
        $hasDefault = (bool) $pdo->query('SELECT COUNT(*) FROM shipping_zones WHERE is_default = 1')->fetchColumn();
        if (!$hasDefault) {
            $pdo->query('UPDATE shipping_zones SET is_default = 1 ORDER BY id ASC LIMIT 1');
        }

        set_flash('success', 'Shipping zone saved.');
        redirect(base_url('admin/shipping-zones.php'));
    }
}

$pageTitle = $isEdit ? 'Edit Shipping Zone' : 'Add Shipping Zone';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
  <div class="form-errors"><?= implode(' ', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<form method="post" class="admin-card" style="padding:26px;max-width:520px;">
  <?= csrf_field() ?>
  <div class="form-group">
    <label for="name">Zone name</label>
    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($zone['name']) ?>" placeholder="e.g. Lagos & Abuja">
  </div>
  <div class="form-group">
    <label for="states">Cities/states matched (comma-separated)</label>
    <input type="text" id="states" name="states" value="<?= htmlspecialchars($zone['states'] ?? '') ?>" placeholder="e.g. Lagos, Abuja, Port Harcourt">
    <p class="form-hint">Matched against the customer's City field at checkout, case-insensitive. Leave blank if this is the default zone.</p>
  </div>
  <div class="form-group">
    <label for="fee">Delivery fee (&#8358;)</label>
    <input type="number" id="fee" name="fee" step="0.01" min="0" required value="<?= htmlspecialchars((string) $zone['fee']) ?>">
  </div>
  <div class="checkbox-row" style="margin-bottom:20px;">
    <input type="checkbox" id="is_default" name="is_default" value="1" <?= $zone['is_default'] ? 'checked' : '' ?>>
    <label for="is_default">Default zone (used when no other zone matches the customer's city)</label>
  </div>
  <div style="display:flex;gap:12px;">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Add zone' ?></button>
    <a href="<?= base_url('admin/shipping-zones.php') ?>" class="btn btn-outline">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
