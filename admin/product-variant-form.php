<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/uploads.php';

const VARIANT_SIZE_RANGE = ['38', '39', '40', '41', '42', '43', '44', '45'];

$productId = (int) ($_GET['product_id'] ?? 0);
$productStmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$productStmt->execute([$productId]);
$product = $productStmt->fetch();
if (!$product) {
    set_flash('error', 'Product not found.');
    redirect(base_url('admin/products.php'));
}

$variantId = isset($_GET['variant_id']) ? (int) $_GET['variant_id'] : 0;
$isEdit = $variantId > 0;
$variant = ['id' => 0, 'color_name' => '', 'color_hex' => '#17140F', 'image' => null];
$variantSizeStock = array_fill_keys(VARIANT_SIZE_RANGE, 0);

if ($isEdit) {
    $vStmt = $pdo->prepare('SELECT * FROM product_variants WHERE id = ? AND product_id = ?');
    $vStmt->execute([$variantId, $productId]);
    $found = $vStmt->fetch();
    if (!$found) {
        set_flash('error', 'Color variant not found.');
        redirect(base_url('admin/product-form.php?id=' . $productId));
    }
    $variant = $found;
    $sizesStmt = $pdo->prepare('SELECT size, stock FROM variant_sizes WHERE variant_id = ?');
    $sizesStmt->execute([$variantId]);
    foreach ($sizesStmt->fetchAll() as $row) {
        $variantSizeStock[$row['size']] = (int) $row['stock'];
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Session expired, please try again.');
        redirect(base_url('admin/product-variant-form.php?product_id=' . $productId) . ($isEdit ? '&variant_id=' . $variantId : ''));
    }

    $variant['color_name'] = trim($_POST['color_name'] ?? '');
    $variant['color_hex'] = trim($_POST['color_hex'] ?? '');
    foreach (VARIANT_SIZE_RANGE as $size) {
        $variantSizeStock[$size] = max(0, (int) ($_POST['stock'][$size] ?? 0));
    }

    if ($variant['color_name'] === '') { $errors[] = 'Color name is required.'; }
    if ($variant['color_hex'] !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $variant['color_hex'])) {
        $errors[] = 'Swatch color must be a valid hex color (e.g. #17140F).';
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

    if (!$errors) {
        if ($isEdit) {
            $sql = 'UPDATE product_variants SET color_name=?, color_hex=?';
            $params = [$variant['color_name'], $variant['color_hex'] ?: null];
            if ($uploadedFilename) { $sql .= ', image=?'; $params[] = $uploadedFilename; }
            $sql .= ' WHERE id=? AND product_id=?';
            $params[] = $variantId;
            $params[] = $productId;
            $pdo->prepare($sql)->execute($params);
        } else {
            $maxSort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), -1) FROM product_variants WHERE product_id = ' . (int) $productId)->fetchColumn();
            $stmt = $pdo->prepare(
                'INSERT INTO product_variants (product_id, color_name, color_hex, image, sort_order) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$productId, $variant['color_name'], $variant['color_hex'] ?: null, $uploadedFilename, $maxSort + 1]);
            $variantId = (int) $pdo->lastInsertId();
        }

        $sizeStmt = $pdo->prepare(
            'INSERT INTO variant_sizes (variant_id, size, stock) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE stock = VALUES(stock)'
        );
        foreach ($variantSizeStock as $size => $stock) {
            $sizeStmt->execute([$variantId, $size, $stock]);
        }

        set_flash('success', 'Color variant saved.');
        redirect(base_url('admin/product-form.php?id=' . $productId));
    }
}

$pageTitle = ($isEdit ? 'Edit' : 'Add') . ' Color Variant';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
  <div class="form-errors"><?= implode(' ', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<p class="text-muted" style="margin-bottom:16px;">For <strong><?= htmlspecialchars($product['name']) ?></strong></p>

<form method="post" enctype="multipart/form-data" class="admin-card" style="padding:26px;">
  <?= csrf_field() ?>

  <div class="form-section">
    <h4>Color details</h4>
    <div class="form-row">
      <div class="form-group">
        <label for="color_name">Color name</label>
        <input type="text" id="color_name" name="color_name" required value="<?= htmlspecialchars($variant['color_name']) ?>" placeholder="e.g. Midnight Black">
      </div>
      <div class="form-group">
        <label for="color_hex">Swatch color</label>
        <div style="display:flex;gap:10px;align-items:center;">
          <input type="color" id="color_hex_picker" value="<?= htmlspecialchars($variant['color_hex'] ?: '#17140F') ?>" style="width:52px;height:44px;padding:4px;flex-shrink:0;" oninput="document.getElementById('color_hex').value=this.value;">
          <input type="text" id="color_hex" name="color_hex" value="<?= htmlspecialchars($variant['color_hex'] ?: '#17140F') ?>" pattern="^#[0-9A-Fa-f]{6}$" oninput="document.getElementById('color_hex_picker').value=this.value;">
        </div>
        <p class="form-hint">Shown as the swatch dot on the product page.</p>
      </div>
    </div>
  </div>

  <div class="form-section">
    <h4>Photo</h4>
    <?php if (!empty($variant['image'])): ?>
      <div class="current-image">
        <img src="<?= base_url('assets/uploads/' . $variant['image']) ?>" alt="">
        <span class="text-muted" style="font-size:0.85rem;">Current photo &mdash; upload a new one below to replace it.</span>
      </div>
    <?php else: ?>
      <p class="form-hint" style="margin-bottom:12px;">No photo for this color yet &mdash; the product's main photo is shown until one is uploaded.</p>
    <?php endif; ?>
    <div class="form-group">
      <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
      <p class="form-hint">JPG, PNG or WEBP, up to 4MB. Shown when a customer selects this color.</p>
    </div>
  </div>

  <div class="form-section">
    <h4>Sizes &amp; stock for this color (UK sizes)</h4>
    <div class="size-stock-grid">
      <?php foreach (VARIANT_SIZE_RANGE as $size): ?>
        <div class="form-group">
          <label for="vstock-<?= $size ?>">Size <?= $size ?></label>
          <input type="number" id="vstock-<?= $size ?>" name="stock[<?= $size ?>]" min="0" value="<?= (int) $variantSizeStock[$size] ?>">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="display:flex;gap:12px;">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Add color' ?></button>
    <a href="<?= base_url('admin/product-form.php?id=' . $productId) ?>" class="btn btn-outline">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
