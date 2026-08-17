<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['form_type'] ?? '') === 'add') {
    $name = trim($_POST['name'] ?? '');
    $accent = trim($_POST['accent_color'] ?? '#FF6A1A');
    if ($name === '') {
        set_flash('error', 'Category name is required.');
    } elseif (!preg_match('/^#[0-9A-Fa-f]{6}$/', $accent)) {
        set_flash('error', 'Pick a valid color.');
    } else {
        $baseSlug = slugify($name);
        $slug = $baseSlug;
        $i = 2;
        while (true) {
            $check = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
            $check->execute([$slug]);
            if (!$check->fetch()) { break; }
            $slug = $baseSlug . '-' . $i++;
        }
        $pdo->prepare('INSERT INTO categories (name, slug, accent_color) VALUES (?, ?, ?)')->execute([$name, $slug, $accent]);
        set_flash('success', 'Category added.');
    }
    redirect(base_url('admin/categories.php'));
}

$categoriesList = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
     FROM categories c ORDER BY c.name"
)->fetchAll();

$pageTitle = 'Categories';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
  <div class="admin-card-head"><h3>Add category</h3></div>
  <div class="admin-card-body">
    <form method="post" style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;">
      <?= csrf_field() ?>
      <input type="hidden" name="form_type" value="add">
      <div class="form-group" style="margin:0;flex:1;min-width:200px;">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required placeholder="e.g. Sandals">
      </div>
      <div class="form-group" style="margin:0;">
        <label for="accent_color">Accent color</label>
        <input type="color" id="accent_color" name="accent_color" value="#FF6A1A" style="width:70px;padding:4px;height:44px;">
      </div>
      <button type="submit" class="btn btn-primary">Add category</button>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h3><?= count($categoriesList) ?> categories</h3></div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>Image</th><th>Color</th><th>Name</th><th>Slug</th><th>Products</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($categoriesList as $cat): ?>
          <tr>
            <td><img src="<?= htmlspecialchars(category_image_url($cat)) ?>" alt="" style="width:46px;height:36px;object-fit:cover;border-radius:6px;"></td>
            <td><span style="display:inline-block;width:22px;height:22px;border-radius:6px;background:<?= htmlspecialchars($cat['accent_color']) ?>;"></span></td>
            <td><?= htmlspecialchars($cat['name']) ?></td>
            <td class="text-muted"><?= htmlspecialchars($cat['slug']) ?></td>
            <td><?= (int) $cat['product_count'] ?></td>
            <td>
              <div class="admin-actions">
                <a href="<?= base_url('admin/category-form.php?id=' . (int) $cat['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
                <form action="<?= base_url('admin/delete-category.php') ?>" method="post" onsubmit="return confirm('Delete this category?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm" <?= $cat['product_count'] > 0 ? 'disabled title="Move or delete its products first"' : '' ?>>Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
