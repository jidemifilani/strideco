<?php
require_once __DIR__ . '/includes/auth.php';

$products = $pdo->query(
    "SELECT p.*, c.name AS category_name
     FROM products p JOIN categories c ON p.category_id = c.id
     ORDER BY p.created_at DESC"
)->fetchAll();

$stockByProduct = [];
foreach ($pdo->query('SELECT product_id, SUM(stock) AS total_stock FROM product_sizes GROUP BY product_id') as $row) {
    $stockByProduct[$row['product_id']] = (int) $row['total_stock'];
}

$pageTitle = 'Products';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
  <div class="admin-card-head">
    <h3><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?></h3>
    <a href="<?= base_url('admin/product-form.php') ?>" class="btn btn-primary btn-sm">+ Add Product</a>
  </div>
  <div class="admin-table-wrap">
    <?php if ($products): ?>
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Featured</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($products as $p): $stock = $stockByProduct[$p['id']] ?? 0; ?>
          <tr>
            <td>
              <div class="admin-product-cell">
                <img class="admin-thumb" src="<?= product_image_url($p) ?>" alt="">
                <span><?= htmlspecialchars($p['name']) ?></span>
              </div>
            </td>
            <td><?= htmlspecialchars($p['category_name']) ?></td>
            <td><?= format_price((float) $p['price']) ?></td>
            <td><?= $stock ?><?= $stock <= 3 ? ' <span class="pill pill-failed">low</span>' : '' ?></td>
            <td><span class="pill pill-<?= htmlspecialchars($p['status']) ?>"><?= htmlspecialchars($p['status']) ?></span></td>
            <td><?= $p['is_featured'] ? '&#9733;' : '&mdash;' ?></td>
            <td>
              <div class="admin-actions">
                <a href="<?= base_url('admin/product-form.php?id=' . (int) $p['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
                <form action="<?= base_url('admin/delete-product.php') ?>" method="post" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="admin-card-body text-muted">No products yet. <a href="<?= base_url('admin/product-form.php') ?>">Add your first product</a>.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
