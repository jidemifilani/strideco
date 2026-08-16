<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/uploads.php';

const SIZE_RANGE = ['38', '39', '40', '41', '42', '43', '44', '45'];

$categoriesList = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $productId > 0;
$product = [
    'id' => 0, 'name' => '', 'category_id' => $categoriesList[0]['id'] ?? 0, 'price' => '',
    'color' => '', 'description' => '', 'features' => '', 'status' => 'active', 'is_featured' => 0, 'image' => null,
    'is_preorder' => 0, 'preorder_available_at' => '',
];
$sizeStock = array_fill_keys(SIZE_RANGE, 0);

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $found = $stmt->fetch();
    if (!$found) {
        set_flash('error', 'Product not found.');
        redirect(base_url('admin/products.php'));
    }
    $product = $found;

    $sizesStmt = $pdo->prepare('SELECT size, stock FROM product_sizes WHERE product_id = ?');
    $sizesStmt->execute([$productId]);
    foreach ($sizesStmt->fetchAll() as $row) {
        $sizeStock[$row['size']] = (int) $row['stock'];
    }
}

$galleryImages = $isEdit
    ? $pdo->query('SELECT * FROM product_images WHERE product_id = ' . (int) $productId . ' ORDER BY sort_order, id')->fetchAll()
    : [];

$productVariants = $isEdit ? get_product_variants($pdo, $productId) : [];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Session expired, please try again.');
        redirect(base_url('admin/product-form.php') . ($isEdit ? '?id=' . $productId : ''));
    }

    $product['name'] = trim($_POST['name'] ?? '');
    $product['category_id'] = (int) ($_POST['category_id'] ?? 0);
    $product['price'] = trim($_POST['price'] ?? '');
    $product['color'] = trim($_POST['color'] ?? '');
    $product['description'] = trim($_POST['description'] ?? '');
    $product['features'] = trim($_POST['features'] ?? '');
    $product['status'] = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $product['is_featured'] = !empty($_POST['is_featured']) ? 1 : 0;
    $product['is_preorder'] = !empty($_POST['is_preorder']) ? 1 : 0;
    $product['preorder_available_at'] = trim($_POST['preorder_available_at'] ?? '');

    foreach (SIZE_RANGE as $size) {
        $sizeStock[$size] = max(0, (int) ($_POST['stock'][$size] ?? 0));
    }

    if ($product['name'] === '') { $errors[] = 'Product name is required.'; }
    if (!is_numeric($product['price']) || (float) $product['price'] <= 0) { $errors[] = 'Enter a valid price.'; }
    $categoryIds = array_column($categoriesList, 'id');
    if (!in_array($product['category_id'], $categoryIds, true)) { $errors[] = 'Select a valid category.'; }
    if ($product['preorder_available_at'] !== '' && strtotime($product['preorder_available_at']) === false) {
        $errors[] = 'Expected availability date is not valid.';
    }

    $uploadedFilename = null;
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = validate_and_save_upload($_FILES['image']);
        if (!$result['ok']) {
            $errors[] = $result['error'];
        } else {
            $uploadedFilename = $result['filename'];
        }
    }

    $galleryFilenames = [];
    if (!empty($_FILES['gallery']['name'][0])) {
        foreach ($_FILES['gallery']['name'] as $i => $galleryName) {
            if ($galleryName === '') { continue; }
            $galleryFile = [
                'name' => $_FILES['gallery']['name'][$i],
                'type' => $_FILES['gallery']['type'][$i],
                'tmp_name' => $_FILES['gallery']['tmp_name'][$i],
                'error' => $_FILES['gallery']['error'][$i],
                'size' => $_FILES['gallery']['size'][$i],
            ];
            $result = validate_and_save_media_upload($galleryFile);
            if (!$result['ok']) {
                $errors[] = 'Gallery item ' . ($i + 1) . ': ' . $result['error'];
            } else {
                $galleryFilenames[] = ['filename' => $result['filename'], 'type' => $result['type']];
            }
        }
    }

    if (!$errors) {
        if ($uploadedFilename) {
            $product['image'] = $uploadedFilename;
        }

        $baseSlug = slugify($product['name']);
        $slug = $baseSlug;
        $i = 2;
        while (true) {
            $check = $pdo->prepare('SELECT id FROM products WHERE slug = ? AND id != ?');
            $check->execute([$slug, $productId]);
            if (!$check->fetch()) { break; }
            $slug = $baseSlug . '-' . $i++;
        }

        $preorderDate = $product['preorder_available_at'] !== '' ? date('Y-m-d', strtotime($product['preorder_available_at'])) : null;

        if ($isEdit) {
            $sql = 'UPDATE products SET category_id=?, name=?, slug=?, description=?, features=?, price=?, color=?, is_featured=?, is_preorder=?, preorder_available_at=?, status=?';
            $params = [$product['category_id'], $product['name'], $slug, $product['description'], $product['features'], $product['price'], $product['color'], $product['is_featured'], $product['is_preorder'], $preorderDate, $product['status']];
            if ($uploadedFilename) { $sql .= ', image=?'; $params[] = $uploadedFilename; }
            $sql .= ' WHERE id=?';
            $params[] = $productId;
            $pdo->prepare($sql)->execute($params);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO products (category_id, name, slug, description, features, price, color, image, is_featured, is_preorder, preorder_available_at, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$product['category_id'], $product['name'], $slug, $product['description'], $product['features'], $product['price'], $product['color'], $product['image'], $product['is_featured'], $product['is_preorder'], $preorderDate, $product['status']]);
            $productId = (int) $pdo->lastInsertId();
        }

        $sizeStmt = $pdo->prepare(
            'INSERT INTO product_sizes (product_id, size, stock) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE stock = VALUES(stock)'
        );
        foreach ($sizeStock as $size => $stock) {
            $sizeStmt->execute([$productId, $size, $stock]);
        }

        if ($galleryFilenames) {
            $maxSort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), -1) FROM product_images WHERE product_id = ' . (int) $productId)->fetchColumn();
            $imgStmt = $pdo->prepare('INSERT INTO product_images (product_id, image, media_type, sort_order) VALUES (?, ?, ?, ?)');
            foreach ($galleryFilenames as $media) {
                $imgStmt->execute([$productId, $media['filename'], $media['type'], ++$maxSort]);
            }
        }

        set_flash('success', 'Product saved.');
        redirect(base_url($isEdit ? 'admin/product-form.php?id=' . $productId : 'admin/products.php'));
    }
}

$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
  <div class="form-errors"><?= implode(' ', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-card" style="padding:26px;">
  <?= csrf_field() ?>

  <div class="form-section">
    <h4>Basic details</h4>
    <div class="form-row">
      <div class="form-group">
        <label for="name">Product name</label>
        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($product['name']) ?>">
      </div>
      <div class="form-group">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id" required>
          <?php foreach ($categoriesList as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" <?= (int) $product['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="price">Price (&#8358;)</label>
        <input type="number" id="price" name="price" step="0.01" min="0" required value="<?= htmlspecialchars((string) $product['price']) ?>">
      </div>
      <div class="form-group">
        <label for="color">Color</label>
        <input type="text" id="color" name="color" value="<?= htmlspecialchars($product['color'] ?? '') ?>" placeholder="e.g. Black / White">
      </div>
    </div>
    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
      <label for="features">Qualities &amp; features</label>
      <textarea id="features" name="features" placeholder="One per line, e.g.&#10;Premium leather upper&#10;Handcrafted stitching&#10;Easy to clean and maintain"><?= htmlspecialchars($product['features'] ?? '') ?></textarea>
      <p class="form-hint">One feature per line &mdash; shown as a bullet list on the product page.</p>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div class="form-group">
        <label>&nbsp;</label>
        <div class="checkbox-row" style="padding-top:12px;">
          <input type="checkbox" id="is_featured" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?>>
          <label for="is_featured">Show in Featured shoes on homepage</label>
        </div>
      </div>
    </div>
  </div>

  <div class="form-section">
    <h4>Pre-order</h4>
    <div class="checkbox-row" style="margin-bottom:14px;">
      <input type="checkbox" id="is_preorder" name="is_preorder" value="1" <?= $product['is_preorder'] ? 'checked' : '' ?>>
      <label for="is_preorder">This item is available for pre-order (not in stock yet)</label>
    </div>
    <div class="form-group">
      <label for="preorder_available_at">Expected availability date (optional)</label>
      <input type="date" id="preorder_available_at" name="preorder_available_at" value="<?= htmlspecialchars((string) $product['preorder_available_at']) ?>">
      <p class="form-hint">While pre-order is on, every size is shown as selectable regardless of the stock numbers below — customers see a "Pre-order" badge and this date instead of normal stock messaging. Turn it off once real stock arrives to go back to normal.</p>
    </div>
  </div>

  <div class="form-section">
    <h4>Photo</h4>
    <?php if (!empty($product['image'])): ?>
      <div class="current-image">
        <img src="<?= product_image_url($product) ?>" alt="">
        <span class="text-muted" style="font-size:0.85rem;">Current photo &mdash; upload a new one below to replace it.</span>
      </div>
    <?php else: ?>
      <p class="form-hint" style="margin-bottom:12px;">No photo uploaded yet &mdash; a generated placeholder is shown on the storefront.</p>
    <?php endif; ?>
    <div class="form-group">
      <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
      <p class="form-hint">JPG, PNG or WEBP, up to 4MB. This is the main photo shown on cards and search.</p>
    </div>
  </div>

  <div class="form-section">
    <h4>Gallery (additional photos &amp; videos)</h4>
    <?php if ($galleryImages): ?>
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <?php foreach ($galleryImages as $img): ?>
          <div style="text-align:center;">
            <div style="position:relative;width:76px;height:76px;border-radius:var(--radius-sm);background:var(--accent-tint);overflow:hidden;margin-bottom:6px;">
              <?php if ($img['media_type'] === 'video'): ?>
                <video src="<?= base_url('assets/uploads/' . $img['image']) ?>" style="width:100%;height:100%;object-fit:cover;display:block;" muted></video>
                <span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.25);color:#fff;font-size:1.3rem;">&#9658;</span>
              <?php else: ?>
                <img src="<?= base_url('assets/uploads/' . $img['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
              <?php endif; ?>
            </div>
            <button type="submit" form="delete-gallery-<?= (int) $img['id'] ?>" class="remove-link" style="background:none;border:none;padding:0;font-size:0.75rem;">Remove</button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="form-group">
      <input type="file" name="gallery[]" accept=".jpg,.jpeg,.png,.webp,.mp4,.webm,.mov" multiple>
      <p class="form-hint">Add extra angles, or a short video (walkthrough, 360&deg; spin) &mdash; shown on the product page. Images up to 4MB, videos up to 20MB. Select multiple files at once.</p>
    </div>
    <?php if (!$isEdit): ?>
      <p class="form-hint">Save the product first, then come back to add gallery media.</p>
    <?php endif; ?>
  </div>

  <div class="form-section">
    <h4>Sizes &amp; stock (UK sizes)</h4>
    <?php if ($productVariants): ?>
      <p class="form-hint" style="margin-bottom:12px;">This product has color variants below — each color has its own sizes/stock, so this base stock is ignored on the storefront.</p>
    <?php endif; ?>
    <div class="size-stock-grid">
      <?php foreach (SIZE_RANGE as $size): ?>
        <div class="form-group">
          <label for="stock-<?= $size ?>">Size <?= $size ?></label>
          <input type="number" id="stock-<?= $size ?>" name="stock[<?= $size ?>]" min="0" value="<?= (int) $sizeStock[$size] ?>">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="display:flex;gap:12px;">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Add product' ?></button>
    <a href="<?= base_url('admin/products.php') ?>" class="btn btn-outline">Cancel</a>
  </div>
</form>

<?php if ($isEdit): ?>
<div class="admin-card" style="padding:26px;margin-top:20px;">
  <div class="admin-card-head" style="margin-bottom:16px;">
    <h3>Color variants</h3>
  </div>
  <p class="form-hint" style="margin-bottom:16px;">Optional. Add a color to let customers pick between options like Black / White, each with its own photo and stock. Leave empty to keep this a single-color product using the "Color" field and stock above.</p>
  <?php if ($productVariants): ?>
    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;">
      <?php foreach ($productVariants as $v): $vStock = array_sum($v['sizes']); ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;border:1px solid var(--border);border-radius:var(--radius-sm);">
          <span style="width:24px;height:24px;border-radius:50%;border:1px solid var(--border);background:<?= htmlspecialchars($v['color_hex'] ?: '#ccc') ?>;flex-shrink:0;"></span>
          <?php if (!empty($v['image'])): ?>
            <img src="<?= base_url('assets/uploads/' . $v['image']) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:var(--radius-sm);">
          <?php endif; ?>
          <div style="flex:1;">
            <strong><?= htmlspecialchars($v['color_name']) ?></strong>
            <div class="text-muted" style="font-size:0.82rem;"><?= $vStock ?> pair<?= $vStock === 1 ? '' : 's' ?> in stock across sizes</div>
          </div>
          <a href="<?= base_url('admin/product-variant-form.php?product_id=' . $productId . '&variant_id=' . (int) $v['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
          <button type="submit" form="delete-variant-<?= (int) $v['id'] ?>" class="btn btn-danger btn-sm">Remove</button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <a href="<?= base_url('admin/product-variant-form.php?product_id=' . $productId) ?>" class="btn btn-outline btn-sm">+ Add color variant</a>
</div>
<?php endif; ?>

<?php foreach ($galleryImages as $img): ?>
  <form id="delete-gallery-<?= (int) $img['id'] ?>" action="<?= base_url('admin/delete-product-image.php') ?>" method="post" onsubmit="return confirm('Remove this photo?');" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $img['id'] ?>">
    <input type="hidden" name="product_id" value="<?= (int) $productId ?>">
  </form>
<?php endforeach; ?>

<?php foreach ($productVariants as $v): ?>
  <form id="delete-variant-<?= (int) $v['id'] ?>" action="<?= base_url('admin/delete-product-variant.php') ?>" method="post" onsubmit="return confirm('Remove this color variant?');" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
    <input type="hidden" name="product_id" value="<?= (int) $productId ?>">
  </form>
<?php endforeach; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
