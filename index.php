<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Shoes for every stride';
require_once __DIR__ . '/includes/header.php';

$featured = $pdo->query(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.accent_color,
       (SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id AND r.status = 'approved') AS rating_avg,
       (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.status = 'approved') AS rating_count
     FROM products p JOIN categories c ON p.category_id = c.id
     WHERE p.status = 'active' AND p.is_featured = 1
     ORDER BY p.created_at DESC LIMIT 8"
)->fetchAll();

$counts = [];
foreach ($pdo->query("SELECT category_id, COUNT(*) AS c FROM products WHERE status='active' GROUP BY category_id") as $row) {
    $counts[$row['category_id']] = $row['c'];
}

$wishlistIds = get_wishlist_ids($pdo);

$sliderProducts = array_slice($featured, 0, 5);
if (!$sliderProducts) {
    $sliderProducts = $pdo->query(
        "SELECT p.*, c.name AS category_name, c.accent_color
         FROM products p JOIN categories c ON p.category_id = c.id
         WHERE p.status = 'active' ORDER BY p.created_at DESC LIMIT 5"
    )->fetchAll();
}

$heroEyebrow = get_setting($pdo, 'hero_eyebrow', 'New season drop');
$heroLine1 = get_setting($pdo, 'hero_headline_line1', 'Every step deserves');
$heroHighlight = get_setting($pdo, 'hero_headline_highlight', 'the right shoe');
$heroSubtext = get_setting($pdo, 'hero_subtext', 'StrideCo brings you sneakers, formal, sport and casual shoes built for comfort and made to last — with fast delivery across Nigeria.');
$heroCtaPrimary = get_setting($pdo, 'hero_cta_primary_label', 'Shop Now');
$heroCtaSecondary = get_setting($pdo, 'hero_cta_secondary_label', 'Browse Sneakers');
?>

<section class="hero hero-fullwidth" id="heroSlider">
  <?php if ($sliderProducts): ?>
    <div class="hero-slider-track">
      <?php foreach ($sliderProducts as $i => $sp):
        $productUrl = base_url('product.php?slug=' . urlencode($sp['slug']));
        $heroBadge = null;
        if (!empty($sp['is_featured'])) {
            $heroBadge = 'Bestseller';
        } elseif (!empty($sp['created_at']) && strtotime($sp['created_at']) > strtotime('-14 days')) {
            $heroBadge = 'New Arrival';
        }
      ?>
        <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>">
          <img src="<?= product_image_url($sp) ?>" alt="<?= htmlspecialchars($sp['name']) ?>" class="hero-slide-bg">
          <a href="<?= $productUrl ?>" class="hero-product-card">
            <div>
              <?php if ($heroBadge): ?><span class="hero-product-badge"><?= htmlspecialchars($heroBadge) ?></span><?php endif; ?>
              <h3><?= htmlspecialchars($sp['name']) ?></h3>
              <span class="hero-product-price"><?= format_price((float) $sp['price']) ?></span>
            </div>
            <span class="hero-product-cta" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="hero-scrim"></div>

  <div class="container hero-content">
    <div class="hero-copy">
      <span class="hero-eyebrow"><?= htmlspecialchars($heroEyebrow) ?></span>
      <h1><?= htmlspecialchars($heroLine1) ?> <em><?= htmlspecialchars($heroHighlight) ?></em>.</h1>
      <p><?= htmlspecialchars($heroSubtext) ?></p>
      <div class="hero-actions">
        <a href="<?= base_url('shop.php') ?>" class="btn btn-primary"><?= htmlspecialchars($heroCtaPrimary) ?></a>
        <a href="<?= base_url('shop.php?category=sneakers') ?>" class="btn btn-outline-light"><?= htmlspecialchars($heroCtaSecondary) ?></a>
      </div>
      <div class="hero-stats">
        <div><strong>12+</strong><span>Curated styles</span></div>
        <div><strong>4</strong><span>Categories</span></div>
        <div><strong>24/7</strong><span>Order support</span></div>
      </div>
    </div>
  </div>

  <?php if (count($sliderProducts) > 1): ?>
    <button type="button" class="hero-slider-nav hero-slider-prev" id="heroSliderPrev" aria-label="Previous">&lsaquo;</button>
    <button type="button" class="hero-slider-nav hero-slider-next" id="heroSliderNext" aria-label="Next">&rsaquo;</button>
    <div class="hero-slider-dots">
      <?php foreach ($sliderProducts as $i => $sp): ?>
        <button type="button" class="hero-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>" aria-label="Show slide <?= $i + 1 ?>"></button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="section-tag">Categories</span>
        <h2>Shop by category</h2>
      </div>
      <a href="<?= base_url('shop.php') ?>" class="btn btn-outline btn-sm">View all shoes</a>
    </div>
    <div class="category-grid">
      <?php foreach ($categories as $cat): ?>
        <a href="<?= base_url('shop.php?category=' . urlencode($cat['slug'])) ?>" class="category-card" style="background: <?= htmlspecialchars($cat['accent_color']) ?>;">
          <span class="cat-icon"><?= htmlspecialchars(mb_substr($cat['name'], 0, 1)) ?></span>
          <h3><?= htmlspecialchars($cat['name']) ?></h3>
          <span><?= (int) ($counts[$cat['id']] ?? 0) ?> styles</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0;">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="section-tag">Handpicked</span>
        <h2>Featured shoes</h2>
        <p>Our most-loved styles this season.</p>
      </div>
    </div>
    <?php if ($featured): ?>
      <div class="product-grid">
        <?php foreach ($featured as $product): echo product_card_html($product, $wishlistIds); endforeach; ?>
      </div>
    <?php else: ?>
      <p>No featured products yet — check back soon.</p>
    <?php endif; ?>
  </div>
</section>

<section class="section" style="padding-top:0;">
  <div class="container">
    <div class="cta-banner">
      <div>
        <h2>Free shipping on orders over <?= format_price(FREE_SHIPPING_THRESHOLD) ?></h2>
        <p>Pay securely online with Paystack, or track your order after checkout.</p>
      </div>
      <a href="<?= base_url('shop.php') ?>" class="btn btn-dark">Start shopping</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
