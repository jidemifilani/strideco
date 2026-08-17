<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/uploads.php';

$id = (int) ($_GET['id'] ?? 0);
$slide = ['id' => 0, 'image' => null, 'headline' => '', 'subtext' => '', 'link_url' => '', 'sort_order' => 0, 'status' => 'active'];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM hero_slides WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        set_flash('error', 'Slide not found.');
        redirect(base_url('admin/hero-slides.php'));
    }
    $slide = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $slide['headline'] = trim($_POST['headline'] ?? '');
    $slide['subtext'] = trim($_POST['subtext'] ?? '');
    $slide['link_url'] = trim($_POST['link_url'] ?? '');
    $slide['sort_order'] = (int) ($_POST['sort_order'] ?? 0);
    $slide['status'] = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = validate_and_save_upload($_FILES['image']);
        if (!$result['ok']) {
            $errors[] = $result['error'];
        } else {
            $slide['image'] = $result['filename'];
        }
    }

    if (!$slide['image']) {
        $errors[] = 'An image is required for this slide.';
    }

    if (!$errors) {
        if ($id > 0) {
            $pdo->prepare('UPDATE hero_slides SET image=?, headline=?, subtext=?, link_url=?, sort_order=?, status=? WHERE id=?')
                ->execute([$slide['image'], $slide['headline'], $slide['subtext'], $slide['link_url'], $slide['sort_order'], $slide['status'], $id]);
            set_flash('success', 'Slide updated.');
        } else {
            $pdo->prepare('INSERT INTO hero_slides (image, headline, subtext, link_url, sort_order, status) VALUES (?,?,?,?,?,?)')
                ->execute([$slide['image'], $slide['headline'], $slide['subtext'], $slide['link_url'], $slide['sort_order'], $slide['status']]);
            set_flash('success', 'Slide added.');
        }
        redirect(base_url('admin/hero-slides.php'));
    }
}

$pageTitle = $id > 0 ? 'Edit Slide' : 'Add Slide';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
  <div class="form-errors"><?= implode(' ', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-card" style="padding:26px;max-width:520px;">
  <?= csrf_field() ?>

  <div class="form-group">
    <label>Slide image</label>
    <?php if (!empty($slide['image'])): ?>
      <img src="<?= htmlspecialchars(base_url('assets/uploads/' . $slide['image'])) ?>" alt="" style="width:100%;max-width:320px;height:160px;object-fit:cover;border-radius:8px;border:1px solid var(--line);display:block;margin-bottom:8px;">
    <?php endif; ?>
    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" <?= $id > 0 ? '' : 'required' ?>>
    <p class="form-hint">JPG, PNG or WEBP, up to 4MB. Wide/landscape photos work best — this fills the full-width homepage banner.</p>
  </div>

  <div class="form-group">
    <label for="headline">Headline (optional)</label>
    <input type="text" id="headline" name="headline" maxlength="150" value="<?= htmlspecialchars($slide['headline'] ?? '') ?>" placeholder="e.g. New arrivals just dropped">
  </div>

  <div class="form-group">
    <label for="subtext">Subtext (optional)</label>
    <input type="text" id="subtext" name="subtext" maxlength="255" value="<?= htmlspecialchars($slide['subtext'] ?? '') ?>" placeholder="e.g. Shop the latest sneakers and boots">
  </div>

  <div class="form-group">
    <label for="link_url">Link when clicked (optional)</label>
    <input type="text" id="link_url" name="link_url" maxlength="255" value="<?= htmlspecialchars($slide['link_url'] ?? '') ?>" placeholder="e.g. shop.php?category=sneakers">
    <p class="form-hint">Leave blank for a non-clickable slide. Use a relative path like <code>shop.php</code> or <code>product.php?slug=...</code></p>
  </div>

  <div class="form-group">
    <label for="sort_order">Order</label>
    <input type="number" id="sort_order" name="sort_order" value="<?= (int) $slide['sort_order'] ?>" style="width:100px;">
    <p class="form-hint">Lower numbers show first.</p>
  </div>

  <div class="form-group">
    <label for="status">Status</label>
    <select id="status" name="status">
      <option value="active" <?= $slide['status'] === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= $slide['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
  </div>

  <div style="display:flex;gap:12px;margin-top:8px;">
    <button type="submit" class="btn btn-primary"><?= $id > 0 ? 'Save changes' : 'Add slide' ?></button>
    <a href="<?= base_url('admin/hero-slides.php') ?>" class="btn btn-outline">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
