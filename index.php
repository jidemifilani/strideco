<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Shoes for every occasion';
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

// Custom admin-managed slides take priority; fall back to Featured products
// (and if there are none of those either, the newest products) so the
// slider is never empty out of the box.
$customSlides = $pdo->query("SELECT * FROM hero_slides WHERE status = 'active' ORDER BY sort_order ASC, id ASC")->fetchAll();

if ($customSlides) {
    $slides = array_map(function ($s) {
        $link = trim((string) $s['link_url']);
        return [
            'image' => base_url('assets/uploads/' . $s['image']),
            'link' => $link === '' ? null : (preg_match('#^https?://#i', $link) ? $link : base_url(ltrim($link, '/'))),
            'badge' => null,
            'title' => $s['headline'],
            'subtitle' => $s['subtext'],
            'price' => null,
        ];
    }, $customSlides);
} else {
    $sliderProducts = array_slice($featured, 0, 5);
    if (!$sliderProducts) {
        $sliderProducts = $pdo->query(
            "SELECT p.*, c.name AS category_name, c.accent_color
             FROM products p JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'active' ORDER BY p.created_at DESC LIMIT 5"
        )->fetchAll();
    }
    $slides = array_map(function ($sp) {
        $badge = null;
        if (!empty($sp['is_featured'])) {
            $badge = 'Bestseller';
        } elseif (!empty($sp['created_at']) && strtotime($sp['created_at']) > strtotime('-14 days')) {
            $badge = 'New Arrival';
        }
        return [
            'image' => product_image_url($sp),
            'link' => base_url('product.php?slug=' . urlencode($sp['slug'])),
            'badge' => $badge,
            'title' => $sp['name'],
            'subtitle' => null,
            'price' => format_price((float) $sp['price']),
        ];
    }, $sliderProducts);
}

$heroEyebrow = get_setting($pdo, 'hero_eyebrow', 'New season drop');
$heroLine1 = get_setting($pdo, 'hero_headline_line1', 'Every step deserves');
$heroHighlight = get_setting($pdo, 'hero_headline_highlight', 'the right shoe');
$heroSubtext = get_setting($pdo, 'hero_subtext', 'Expandable Collection brings you sneakers, formal, sport and casual shoes built for comfort and made to last — with fast delivery across Nigeria.');
$heroCtaPrimary = get_setting($pdo, 'hero_cta_primary_label', 'Shop Now');
$heroCtaSecondary = get_setting($pdo, 'hero_cta_secondary_label', 'Browse Sneakers');
?>

<section class="hero hero-fullwidth" id="heroSlider">
  <?php if ($slides): ?>
    <div class="hero-slider-track">
      <?php foreach ($slides as $i => $slide): $cardTag = $slide['link'] ? 'a' : 'div'; ?>
        <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>">
          <img src="<?= htmlspecialchars($slide['image']) ?>" alt="<?= htmlspecialchars($slide['title'] ?? '') ?>" class="hero-slide-bg">
          <?php if ($slide['title'] || $slide['price']): ?>
            <<?= $cardTag ?> <?= $slide['link'] ? 'href="' . htmlspecialchars($slide['link']) . '"' : '' ?> class="hero-product-card">
              <div>
                <?php if ($slide['badge']): ?><span class="hero-product-badge"><?= htmlspecialchars($slide['badge']) ?></span><?php endif; ?>
                <?php if ($slide['title']): ?><h3><?= htmlspecialchars($slide['title']) ?></h3><?php endif; ?>
                <?php if ($slide['subtitle']): ?><p class="hero-product-subtitle"><?= htmlspecialchars($slide['subtitle']) ?></p><?php endif; ?>
                <?php if ($slide['price']): ?><span class="hero-product-price"><?= $slide['price'] ?></span><?php endif; ?>
              </div>
              <?php if ($slide['link']): ?>
                <span class="hero-product-cta" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
              <?php endif; ?>
            </<?= $cardTag ?>>
          <?php endif; ?>
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

  <?php if (count($slides) > 1): ?>
    <button type="button" class="hero-slider-nav hero-slider-prev" id="heroSliderPrev" aria-label="Previous">&lsaquo;</button>
    <button type="button" class="hero-slider-nav hero-slider-next" id="heroSliderNext" aria-label="Next">&rsaquo;</button>
    <div class="hero-slider-dots">
      <?php foreach ($slides as $i => $slide): ?>
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
