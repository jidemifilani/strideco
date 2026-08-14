<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tax_rate_percent']) && verify_csrf()) {
    $taxRate = trim($_POST['tax_rate_percent']);
    if (is_numeric($taxRate) && (float) $taxRate >= 0 && (float) $taxRate <= 100) {
        $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES ("tax_rate_percent", ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        )->execute([$taxRate]);
        set_flash('success', 'Tax rate saved.');
    } else {
        set_flash('error', 'Tax rate must be a number between 0 and 100.');
    }
    redirect(base_url('admin/shipping-zones.php'));
}

$zones = get_shipping_zones($pdo);
$taxRatePercent = get_tax_rate_percent($pdo);

$pageTitle = 'Shipping & Tax';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-card" style="margin-bottom:20px;">
  <div class="admin-card-head"><h3>Tax rate</h3></div>
  <div class="admin-card-body">
    <form method="post" style="display:flex;gap:12px;align-items:flex-end;max-width:340px;">
      <?= csrf_field() ?>
      <div class="form-group" style="margin:0;flex:1;">
        <label for="tax_rate_percent">Tax rate (%)</label>
        <input type="number" id="tax_rate_percent" name="tax_rate_percent" step="0.01" min="0" max="100" value="<?= htmlspecialchars((string) $taxRatePercent) ?>">
      </div>
      <button type="submit" class="btn btn-primary">Save</button>
    </form>
    <p class="form-hint" style="margin-top:10px;">Applied to (subtotal &minus; discount) at checkout. Leave at 0 to charge no tax.</p>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-head">
    <h3><?= count($zones) ?> shipping zone<?= count($zones) === 1 ? '' : 's' ?></h3>
    <a href="<?= base_url('admin/shipping-zone-form.php') ?>" class="btn btn-primary btn-sm">+ Add Zone</a>
  </div>
  <div class="admin-table-wrap">
    <?php if ($zones): ?>
    <table class="admin-table">
      <thead><tr><th>Zone</th><th>Matches</th><th>Fee</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($zones as $z): ?>
          <tr>
            <td><strong><?= htmlspecialchars($z['name']) ?></strong> <?php if ($z['is_default']): ?><span class="pill pill-active">Default</span><?php endif; ?></td>
            <td class="text-muted"><?= $z['is_default'] ? 'Everywhere else' : htmlspecialchars($z['states'] ?: '&mdash;') ?></td>
            <td><?= format_price((float) $z['fee']) ?></td>
            <td>
              <div class="admin-actions">
                <a href="<?= base_url('admin/shipping-zone-form.php?id=' . (int) $z['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
                <form action="<?= base_url('admin/delete-shipping-zone.php') ?>" method="post" onsubmit="return confirm('Delete this shipping zone?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $z['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="admin-card-body text-muted">No shipping zones yet — orders will use the flat SHIPPING_FEE from config/config.php until you add one.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
