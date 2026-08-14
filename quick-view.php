<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

$slug = trim($_GET['slug'] ?? '');
$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.accent_color
     FROM products p JOIN categories c ON p.category_id = c.id
     WHERE p.slug = ? AND p.status = 'active'"
);
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    echo '<div class="modal-loading">Shoe not found.</div>';
    exit;
}

$hasVariants = !empty(get_product_variants($pdo, (int) $product['id']));

$sizesStmt = $pdo->prepare('SELECT * FROM product_sizes WHERE product_id = ? ORDER BY CAST(size AS UNSIGNED)');
$sizesStmt->execute([$product['id']]);
$sizes = $sizesStmt->fetchAll();
$anyInStock = false;
foreach ($sizes as $s) { if ($s['stock'] > 0) { $anyInStock = true; break; } }

$galleryStmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
$galleryStmt->execute([$product['id']]);
$galleryImages = $galleryStmt->fetchAll();
$qvThumbs = [['url' => product_image_url($product), 'type' => 'image']];
foreach ($galleryImages as $img) {
    $qvThumbs[] = ['url' => base_url('assets/uploads/' . $img['image']), 'type' => $img['media_type']];
}

$rating = get_rating_summary($pdo, $product['id']);
$productUrl = base_url('product.php?slug=' . urlencode($product['slug']));
?>
<div class="qv-layout">
  <div class="qv-image">
    <img id="qvMainImage" src="<?= htmlspecialchars($qvThumbs[0]['url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
    <video id="qvMainVideo" controls playsinline style="display:none;width:100%;height:100%;object-fit:contain;background:var(--ink);"></video>
    <?php if (count($qvThumbs) > 1): ?>
      <div class="qv-thumbs">
        <?php foreach ($qvThumbs as $i => $thumb): ?>
          <button type="button" class="qv-thumb <?= $i === 0 ? 'active' : '' ?>" data-src="<?= htmlspecialchars($thumb['url']) ?>" data-type="<?= $thumb['type'] ?>">
            <?php if ($thumb['type'] === 'video'): ?>
              <video src="<?= htmlspecialchars($thumb['url']) ?>" muted></video>
              <span class="pd-thumb-play" style="font-size:0.8rem;">&#9658;</span>
            <?php else: ?>
              <img src="<?= htmlspecialchars($thumb['url']) ?>" alt="View <?= $i + 1 ?>">
            <?php endif; ?>
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="qv-info">
    <span class="product-cat"><?= htmlspecialchars($product['category_name']) ?></span>
    <h2><?= htmlspecialchars($product['name']) ?></h2>
    <?php if ($rating['count'] > 0): ?>
      <div class="rating-summary" style="margin-bottom:10px;">
        <?= star_rating_html($rating['avg'], 13) ?>
        <span><?= number_format($rating['avg'], 1) ?> (<?= $rating['count'] ?>)</span>
      </div>
    <?php endif; ?>
    <div class="pd-price"><?= format_price((float) $product['price']) ?></div>
    <p style="color:var(--ink-soft);margin-bottom:16px;"><?= htmlspecialchars(mb_substr($product['description'], 0, 140)) ?><?= mb_strlen($product['description']) > 140 ? '…' : '' ?></p>

    <?php if ($hasVariants): ?>
      <p class="text-muted" style="margin-bottom:16px;">This shoe comes in multiple colors — view full details to pick one and add it to your cart.</p>
      <a href="<?= $productUrl ?>" class="btn btn-primary">View full details</a>
    <?php else: ?>
      <?php if (!$anyInStock): ?>
        <p class="form-error" style="margin-bottom:16px;">Out of stock in all sizes.</p>
      <?php endif; ?>

      <form action="<?= base_url('cart-actions.php') ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_SERVER['HTTP_REFERER'] ?? base_url('shop.php')) ?>">

        <label style="font-size:0.82rem;">Size (UK)</label>
        <div class="size-grid" style="margin-bottom:16px;">
          <?php foreach ($sizes as $s): $disabled = $s['stock'] <= 0; ?>
            <div class="size-option <?= $disabled ? 'disabled' : '' ?>" style="transform:scale(0.85);transform-origin:left center;">
              <input type="radio" name="size" id="qv-size-<?= htmlspecialchars($s['size']) ?>" value="<?= htmlspecialchars($s['size']) ?>" <?= $disabled ? 'disabled' : '' ?> required>
              <label for="qv-size-<?= htmlspecialchars($s['size']) ?>"><?= htmlspecialchars($s['size']) ?></label>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <button type="submit" class="btn btn-primary" <?= $anyInStock ? '' : 'disabled' ?>>Add to Cart</button>
          <a href="<?= $productUrl ?>" class="btn btn-outline">View full details</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
