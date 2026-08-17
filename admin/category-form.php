<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/uploads.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    set_flash('error', 'Category not found.');
    redirect(base_url('admin/categories.php'));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $name = trim($_POST['name'] ?? '');
    $accent = trim($_POST['accent_color'] ?? '');

    if ($name === '') { $errors[] = 'Name is required.'; }
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $accent)) { $errors[] = 'Pick a valid color.'; }

    $image = $category['image'];
    if (!empty($_POST['remove_image'])) {
        $image = null;
    }
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = validate_and_save_upload($_FILES['image']);
        if (!$result['ok']) {
            $errors[] = $result['error'];
        } else {
            $image = $result['filename'];
        }
    }

    if (!$errors) {
        $pdo->prepare('UPDATE categories SET name = ?, accent_color = ?, image = ? WHERE id = ?')->execute([$name, $accent, $image, $id]);
        set_flash('success', 'Category updated.');
        redirect(base_url('admin/categories.php'));
    }
    $category['name'] = $name;
    $category['accent_color'] = $accent;
    $category['image'] = $image;
}

$pageTitle = 'Edit Category';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
  <div class="form-errors"><?= implode(' ', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-card" style="padding:26px;max-width:420px;">
  <?= csrf_field() ?>
  <div class="form-group">
    <label for="name">Name</label>
    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($category['name']) ?>">
  </div>
  <div class="form-group">
    <label for="accent_color">Accent color</label>
    <input type="color" id="accent_color" name="accent_color" value="<?= htmlspecialchars($category['accent_color']) ?>" style="width:70px;padding:4px;height:44px;">
  </div>
  <div class="form-group">
    <label>Category image</label>
    <?php if (!empty($category['image'])): ?>
      <div style="margin-bottom:10px;">
        <img src="<?= htmlspecialchars(category_image_url($category)) ?>" alt="" style="width:120px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--line);display:block;margin-bottom:6px;">
        <label style="font-weight:400;font-size:0.85rem;"><input type="checkbox" name="remove_image" value="1"> Remove current image</label>
      </div>
    <?php endif; ?>
    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
    <p class="form-hint">JPG, PNG or WEBP, up to 4MB. Shown on the shop page's "Shop by category" tiles.</p>
  </div>
  <p class="form-hint" style="margin-bottom:18px;">Slug: <?= htmlspecialchars($category['slug']) ?> (not editable — used in URLs)</p>
  <div style="display:flex;gap:12px;">
    <button type="submit" class="btn btn-primary">Save changes</button>
    <a href="<?= base_url('admin/categories.php') ?>" class="btn btn-outline">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
