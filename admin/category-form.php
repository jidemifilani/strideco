<?php
require_once __DIR__ . '/includes/auth.php';

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

    if (!$errors) {
        $pdo->prepare('UPDATE categories SET name = ?, accent_color = ? WHERE id = ?')->execute([$name, $accent, $id]);
        set_flash('success', 'Category updated.');
        redirect(base_url('admin/categories.php'));
    }
    $category['name'] = $name;
    $category['accent_color'] = $accent;
}

$pageTitle = 'Edit Category';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
  <div class="form-errors"><?= implode(' ', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<form method="post" class="admin-card" style="padding:26px;max-width:420px;">
  <?= csrf_field() ?>
  <div class="form-group">
    <label for="name">Name</label>
    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($category['name']) ?>">
  </div>
  <div class="form-group">
    <label for="accent_color">Accent color</label>
    <input type="color" id="accent_color" name="accent_color" value="<?= htmlspecialchars($category['accent_color']) ?>" style="width:70px;padding:4px;height:44px;">
  </div>
  <p class="form-hint" style="margin-bottom:18px;">Slug: <?= htmlspecialchars($category['slug']) ?> (not editable — used in URLs)</p>
  <div style="display:flex;gap:12px;">
    <button type="submit" class="btn btn-primary">Save changes</button>
    <a href="<?= base_url('admin/categories.php') ?>" class="btn btn-outline">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
