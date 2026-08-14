<?php
require_once __DIR__ . '/includes/auth.php';
require_super_admin();

const SETTINGS_FIELDS = [
    'site_name', 'accent_color',
    'hero_eyebrow', 'hero_headline_line1', 'hero_headline_highlight', 'hero_subtext',
    'hero_cta_primary_label', 'hero_cta_secondary_label',
    'contact_email', 'contact_phone', 'contact_address',
    'social_instagram', 'social_twitter', 'social_tiktok',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $accent = trim($_POST['accent_color'] ?? '');
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $accent)) {
        $errors[] = 'Accent color must be a valid hex color (e.g. #FF5A1F).';
    }
    if (trim($_POST['site_name'] ?? '') === '') {
        $errors[] = 'Site name is required.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        foreach (SETTINGS_FIELDS as $field) {
            $stmt->execute([$field, trim($_POST[$field] ?? '')]);
        }
        set_flash('success', 'Settings saved.');
        redirect(base_url('admin/settings.php'));
    }
}

// Reload fresh (bypasses the request-level cache so edits show immediately)
$settings = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM settings') as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$val = fn(string $key) => htmlspecialchars($settings[$key] ?? '');

$pageTitle = 'Theme & Content';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
  <div class="form-errors"><?= implode(' ', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<form method="post">
  <?= csrf_field() ?>

  <div class="admin-card">
    <div class="admin-card-head"><h3>Branding</h3></div>
    <div class="admin-card-body">
      <div class="form-row">
        <div class="form-group">
          <label for="site_name">Site name</label>
          <input type="text" id="site_name" name="site_name" required value="<?= $val('site_name') ?>">
        </div>
        <div class="form-group">
          <label for="accent_color">Accent color</label>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="color" id="accent_color_picker" value="<?= $val('accent_color') ?: '#FF5A1F' ?>" style="width:52px;height:44px;padding:4px;flex-shrink:0;" oninput="document.getElementById('accent_color').value=this.value;">
            <input type="text" id="accent_color" name="accent_color" required value="<?= $val('accent_color') ?: '#FF5A1F' ?>" pattern="^#[0-9A-Fa-f]{6}$" oninput="document.getElementById('accent_color_picker').value=this.value;">
          </div>
          <p class="form-hint">Applied across buttons, prices, and highlights site-wide.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h3>Homepage hero</h3></div>
    <div class="admin-card-body">
      <div class="form-group">
        <label for="hero_eyebrow">Eyebrow tag</label>
        <input type="text" id="hero_eyebrow" name="hero_eyebrow" value="<?= $val('hero_eyebrow') ?>" placeholder="e.g. New season drop">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="hero_headline_line1">Headline</label>
          <input type="text" id="hero_headline_line1" name="hero_headline_line1" value="<?= $val('hero_headline_line1') ?>" placeholder="e.g. Every step deserves">
        </div>
        <div class="form-group">
          <label for="hero_headline_highlight">Headline highlight</label>
          <input type="text" id="hero_headline_highlight" name="hero_headline_highlight" value="<?= $val('hero_headline_highlight') ?>" placeholder="e.g. the right shoe">
          <p class="form-hint">Shown in the accent color, right after the headline.</p>
        </div>
      </div>
      <div class="form-group">
        <label for="hero_subtext">Subtext</label>
        <textarea id="hero_subtext" name="hero_subtext"><?= $val('hero_subtext') ?></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="hero_cta_primary_label">Primary button label</label>
          <input type="text" id="hero_cta_primary_label" name="hero_cta_primary_label" value="<?= $val('hero_cta_primary_label') ?>">
        </div>
        <div class="form-group">
          <label for="hero_cta_secondary_label">Secondary button label</label>
          <input type="text" id="hero_cta_secondary_label" name="hero_cta_secondary_label" value="<?= $val('hero_cta_secondary_label') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h3>Contact &amp; social</h3></div>
    <div class="admin-card-body">
      <div class="form-row">
        <div class="form-group">
          <label for="contact_email">Contact email</label>
          <input type="email" id="contact_email" name="contact_email" value="<?= $val('contact_email') ?>">
        </div>
        <div class="form-group">
          <label for="contact_phone">Contact phone</label>
          <input type="text" id="contact_phone" name="contact_phone" value="<?= $val('contact_phone') ?>">
        </div>
      </div>
      <div class="form-group">
        <label for="contact_address">Address</label>
        <input type="text" id="contact_address" name="contact_address" value="<?= $val('contact_address') ?>">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="social_instagram">Instagram URL</label>
          <input type="text" id="social_instagram" name="social_instagram" value="<?= $val('social_instagram') ?>" placeholder="https://instagram.com/yourshop">
        </div>
        <div class="form-group">
          <label for="social_twitter">X / Twitter URL</label>
          <input type="text" id="social_twitter" name="social_twitter" value="<?= $val('social_twitter') ?>" placeholder="https://x.com/yourshop">
        </div>
      </div>
      <div class="form-group">
        <label for="social_tiktok">TikTok URL</label>
        <input type="text" id="social_tiktok" name="social_tiktok" value="<?= $val('social_tiktok') ?>" placeholder="https://tiktok.com/@yourshop">
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Save settings</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
