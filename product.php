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
    $pageTitle = 'Shoe not found';
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container empty-state"><h3>Shoe not found</h3><p>It may have been removed or is no longer available.</p><a href="' . base_url('shop.php') . '" class="btn btn-outline btn-sm">Back to shop</a></div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$sizesStmt = $pdo->prepare('SELECT * FROM product_sizes WHERE product_id = ? ORDER BY CAST(size AS UNSIGNED)');
$sizesStmt->execute([$product['id']]);
$sizes = $sizesStmt->fetchAll();

$variants = get_product_variants($pdo, (int) $product['id']);
$hasVariants = !empty($variants);

// When color variants exist, the size grid reflects the currently selected
// variant's own stock (not the base products/product_sizes stock) — default
// to the first variant that has any stock, or the first variant otherwise.
$activeVariant = null;
if ($hasVariants) {
    $activeVariant = $variants[0];
    foreach ($variants as $v) {
        if (array_sum($v['sizes']) > 0) { $activeVariant = $v; break; }
    }
    $sizeStockMap = $activeVariant['sizes'];
} else {
    $sizeStockMap = [];
    foreach ($sizes as $s) {
        $sizeStockMap[$s['size']] = (int) $s['stock'];
    }
}

$anyInStock = false;
$totalStock = 0;
foreach ($sizeStockMap as $stock) {
    if ($stock > 0) { $anyInStock = true; }
    $totalStock += $stock;
}

$galleryStmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
$galleryStmt->execute([$product['id']]);
$galleryImages = $galleryStmt->fetchAll();
$mainImage = $activeVariant ? variant_image_url($activeVariant, $product) : product_image_url($product);
$thumbs = [['url' => $mainImage, 'type' => 'image']];
foreach ($galleryImages as $img) {
    $thumbs[] = ['url' => base_url('assets/uploads/' . $img['image']), 'type' => $img['media_type']];
}

$rating = get_rating_summary($pdo, $product['id']);
$reviewsStmt = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? AND status = 'approved' ORDER BY created_at DESC");
$reviewsStmt->execute([$product['id']]);
$reviews = $reviewsStmt->fetchAll();

$wishlistIds = get_wishlist_ids($pdo);
$inWishlist = in_array((int) $product['id'], $wishlistIds, true);

$relatedStmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.accent_color,
       (SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id AND r.status = 'approved') AS rating_avg,
       (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.status = 'approved') AS rating_count
     FROM products p JOIN categories c ON p.category_id = c.id
     WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
     ORDER BY RAND() LIMIT 4"
);
$relatedStmt->execute([$product['category_id'], $product['id']]);
$related = $relatedStmt->fetchAll();

$pageTitle = $product['name'];
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="breadcrumb">
      <a href="<?= base_url('index.php') ?>">Home</a> /
      <a href="<?= base_url('shop.php?category=' . urlencode($product['category_slug'])) ?>"><?= htmlspecialchars($product['category_name']) ?></a> /
      <?= htmlspecialchars($product['name']) ?>
    </div>

    <div class="product-detail">
      <div class="pd-media">
        <div class="pd-gallery">
          <img id="mainProductImage" src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($product['name']) ?>" width="600" height="600">
          <video id="mainProductVideo" controls playsinline style="display:none;"></video>
          <button type="button" class="pd-zoom-btn" id="pdZoomBtn" aria-label="Zoom image">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="M21 21l-4.3-4.3M11 8v6M8 11h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          </button>
        </div>
        <?php if (count($thumbs) > 1): ?>
          <div class="pd-thumbs">
            <?php foreach ($thumbs as $i => $thumb): ?>
              <button type="button" class="pd-thumb <?= $i === 0 ? 'active' : '' ?>" data-src="<?= htmlspecialchars($thumb['url']) ?>" data-type="<?= $thumb['type'] ?>">
                <?php if ($thumb['type'] === 'video'): ?>
                  <video src="<?= htmlspecialchars($thumb['url']) ?>" muted></video>
                  <span class="pd-thumb-play">&#9658;</span>
                <?php else: ?>
                  <img src="<?= htmlspecialchars($thumb['url']) ?>" alt="View <?= $i + 1 ?>">
                <?php endif; ?>
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="pd-info">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
          <span class="product-cat"><?= htmlspecialchars($product['category_name']) ?><?php if (!$hasVariants && !empty($product['color'])): ?> &middot; <?= htmlspecialchars($product['color']) ?><?php endif; ?></span>
          <form action="<?= base_url('wishlist-actions.php') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
            <button type="submit" class="wishlist-btn <?= $inWishlist ? 'active' : '' ?>" aria-label="Toggle wishlist" style="position:static;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 20.5s-7.5-4.6-9.8-9C.7 8.1 2 4.5 5.4 3.6c2-.5 4 .3 5.1 2 .3.4.9.4 1.2 0 1.1-1.7 3.1-2.5 5.1-2 3.4.9 4.7 4.5 3.2 7.9-2.3 4.4-9.8 9-9.8 9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            </button>
          </form>
        </div>
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <?php if ($rating['count'] > 0): ?>
          <div class="rating-summary" style="margin-bottom:14px;">
            <?= star_rating_html($rating['avg'], 15) ?>
            <span><?= number_format($rating['avg'], 1) ?> (<?= $rating['count'] ?> review<?= $rating['count'] === 1 ? '' : 's' ?>)</span>
          </div>
        <?php endif; ?>
        <div class="pd-price"><?= format_price((float) $product['price']) ?></div>
        <div class="pd-desc"><p><?= nl2br(htmlspecialchars($product['description'])) ?></p></div>

        <?php $featureLines = array_filter(array_map('trim', explode("\n", $product['features'] ?? ''))); ?>
        <?php if ($featureLines): ?>
          <ul class="pd-features">
            <?php foreach ($featureLines as $line): ?>
              <li>
                <svg width="15" height="15" viewBox="0 0 16 16" fill="none"><path d="M3 8.5l3 3 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span><?= htmlspecialchars($line) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <p id="stockWarning" class="<?= !$anyInStock ? 'form-error' : 'stock-low-note' ?>" style="<?= $anyInStock && $totalStock > 8 ? 'display:none;' : '' ?>margin-bottom:20px;">
          <?= !$anyInStock ? 'This shoe is currently out of stock in all sizes.' : 'Only ' . $totalStock . ' pair' . ($totalStock === 1 ? '' : 's') . ' left in stock — order soon!' ?>
        </p>

        <form action="<?= base_url('cart-actions.php') ?>" method="post" id="addToCartForm">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
          <input type="hidden" name="variant_id" id="selectedVariantId" value="<?= $activeVariant ? (int) $activeVariant['id'] : '' ?>">
          <input type="hidden" name="redirect_to" value="<?= htmlspecialchars(base_url('product.php?slug=' . urlencode($product['slug']))) ?>">

          <?php if ($hasVariants): ?>
            <div class="size-label-row">
              <label>Select color</label>
            </div>
            <div class="color-swatch-row" id="colorSwatchRow">
              <?php foreach ($variants as $v): ?>
                <div>
                  <div class="color-swatch">
                    <input type="radio" name="variant_display" id="variant-<?= (int) $v['id'] ?>" value="<?= (int) $v['id'] ?>" <?= $activeVariant && $activeVariant['id'] === $v['id'] ? 'checked' : '' ?>>
                    <label for="variant-<?= (int) $v['id'] ?>" class="color-swatch-dot" style="background:<?= htmlspecialchars($v['color_hex'] ?: '#ccc') ?>;"></label>
                  </div>
                  <span class="color-swatch-label"><?= htmlspecialchars($v['color_name']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="size-label-row">
            <label>Select size (UK)</label>
            <a href="<?= base_url('faq.php#faq-sizing') ?>" target="_blank" class="find-size-link">Find my size</a>
          </div>
          <div class="size-grid size-grid-cols" id="sizeGrid">
            <?php foreach ($sizeStockMap as $size => $stock): $disabled = $stock <= 0; ?>
              <div class="size-option <?= $disabled ? 'disabled' : '' ?>" data-size="<?= htmlspecialchars($size) ?>">
                <input type="radio" name="size" id="size-<?= htmlspecialchars($size) ?>" value="<?= htmlspecialchars($size) ?>" <?= $disabled ? 'disabled' : '' ?> required>
                <label for="size-<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></label>
              </div>
            <?php endforeach; ?>
          </div>

          <label>Quantity</label>
          <div class="qty-row">
            <div class="qty-stepper">
              <button type="button" data-step="-1" aria-label="Decrease quantity">&minus;</button>
              <input type="number" name="qty" value="1" min="1" max="10" aria-label="Quantity">
              <button type="button" data-step="1" aria-label="Increase quantity">&plus;</button>
            </div>
            <button type="submit" id="addToCartBtn" class="btn btn-primary" <?= $anyInStock ? '' : 'disabled' ?>>
              Add to Cart
            </button>
          </div>
        </form>

        <div class="pd-meta">
          <span>Free shipping on orders over <?= format_price(FREE_SHIPPING_THRESHOLD) ?></span>
          <span>Secure checkout powered by Paystack</span>
          <span>Easy exchanges within 7 days of delivery</span>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="lightbox-overlay" id="lightboxOverlay">
  <button type="button" class="lightbox-close" id="lightboxClose" aria-label="Close">&times;</button>
  <?php if (count($thumbs) > 1): ?>
    <button type="button" class="lightbox-nav lightbox-prev" id="lightboxPrev" aria-label="Previous image">&lsaquo;</button>
  <?php endif; ?>
  <img class="lightbox-image" id="lightboxImage" src="" alt="<?= htmlspecialchars($product['name']) ?>">
  <?php if (count($thumbs) > 1): ?>
    <button type="button" class="lightbox-nav lightbox-next" id="lightboxNext" aria-label="Next image">&rsaquo;</button>
  <?php endif; ?>
</div>
<script>window.PRODUCT_GALLERY_IMAGES = <?= json_encode(array_values($thumbs)) ?>;</script>
<?php
$variantsForJs = array_map(function ($v) use ($product) {
    return ['id' => (int) $v['id'], 'image' => variant_image_url($v, $product), 'sizes' => $v['sizes']];
}, $variants);
?>
<script>window.PRODUCT_VARIANTS = <?= json_encode($variantsForJs) ?>;</script>

<div class="sticky-cart-bar">
  <div>
    <div class="sticky-cart-name"><?= htmlspecialchars($product['name']) ?></div>
    <div class="sticky-cart-price"><?= format_price((float) $product['price']) ?></div>
  </div>
  <?php if ($anyInStock): ?>
    <button type="button" id="stickyCartBtn" class="btn btn-primary btn-sm">Select Size</button>
  <?php else: ?>
    <span class="btn btn-outline btn-sm" style="opacity:0.6;">Out of Stock</span>
  <?php endif; ?>
</div>

<section class="section" style="padding-top:0;">
  <div class="container" style="max-width:760px;">
    <div class="section-head">
      <div><span class="section-tag">Reviews</span><h2>What customers say</h2></div>
    </div>

    <?php if ($reviews): ?>
      <div style="display:flex;flex-direction:column;gap:20px;margin-bottom:36px;">
        <?php foreach ($reviews as $r): ?>
          <div class="checkout-card" style="padding:20px 22px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
              <strong><?= htmlspecialchars($r['customer_name']) ?></strong>
              <span class="text-muted" style="font-size:0.78rem;"><?= date('d M Y', strtotime($r['created_at'])) ?></span>
            </div>
            <?= star_rating_html((float) $r['rating'], 13) ?>
            <p style="margin-top:10px;margin-bottom:0;"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-muted" style="margin-bottom:32px;">No reviews yet — be the first to share your thoughts.</p>
    <?php endif; ?>

    <div class="checkout-card">
      <h3>Write a review</h3>
      <form action="<?= base_url('review-actions.php') ?>" method="post">
        <?= csrf_field() ?>
        <?= honeypot_field() ?>
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <input type="hidden" name="slug" value="<?= htmlspecialchars($product['slug']) ?>">
        <?php if (!is_customer_logged_in()): ?>
          <div class="form-group">
            <label for="customer_name">Your name</label>
            <input type="text" id="customer_name" name="customer_name" required>
          </div>
        <?php endif; ?>
        <div class="form-group">
          <label for="rating">Rating</label>
          <select id="rating" name="rating" required>
            <option value="5">★★★★★ Excellent</option>
            <option value="4">★★★★ Good</option>
            <option value="3">★★★ Average</option>
            <option value="2">★★ Poor</option>
            <option value="1">★ Terrible</option>
          </select>
        </div>
        <div class="form-group">
          <label for="comment">Your review</label>
          <textarea id="comment" name="comment" required placeholder="Tell other shoppers what you think..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit review</button>
        <p class="form-hint" style="margin-top:10px;">Reviews are checked by our team before they go live.</p>
      </form>
    </div>
  </div>
</section>

<?php if ($related): ?>
<section class="section" style="padding-top:0;">
  <div class="container">
    <div class="section-head">
      <div><span class="section-tag">You might also like</span><h2>More in <?= htmlspecialchars($product['category_name']) ?></h2></div>
    </div>
    <div class="product-grid">
      <?php foreach ($related as $r): echo product_card_html($r, $wishlistIds); endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="mobile-cart-spacer"></div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
