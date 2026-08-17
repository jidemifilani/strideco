<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$categorySlug = trim($_GET['category'] ?? '');
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) $_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : null;

$activeCategory = null;
if ($categorySlug !== '') {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = ?');
    $stmt->execute([$categorySlug]);
    $activeCategory = $stmt->fetch();
}

$sortMap = [
    'newest' => 'p.created_at DESC',
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name_asc' => 'p.name ASC',
    'rating' => 'rating_avg DESC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['newest'];

$where = ["p.status = 'active'"];
$params = [];
if ($activeCategory) {
    $where[] = 'p.category_id = ?';
    $params[] = $activeCategory['id'];
}
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($minPrice !== null) {
    $where[] = 'p.price >= ?';
    $params[] = $minPrice;
}
if ($maxPrice !== null) {
    $where[] = 'p.price <= ?';
    $params[] = $maxPrice;
}
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.accent_color,
       (SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id AND r.status = 'approved') AS rating_avg,
       (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.status = 'approved') AS rating_count
     FROM products p JOIN categories c ON p.category_id = c.id
     WHERE $whereSql ORDER BY $orderBy"
);
$stmt->execute($params);
$products = $stmt->fetchAll();
$wishlistIds = get_wishlist_ids($pdo);

$counts = [];
foreach ($pdo->query("SELECT category_id, COUNT(*) AS c FROM products WHERE status='active' GROUP BY category_id") as $row) {
    $counts[$row['category_id']] = $row['c'];
}
$totalCount = array_sum($counts);

// Build query params for filter links, preserving everything except what each link changes.
$baseParams = array_filter([
    'category' => $categorySlug ?: null,
    'search' => $search ?: null,
    'sort' => $sort !== 'newest' ? $sort : null,
], fn($v) => $v !== null);

function shop_filter_url(array $base, array $overrides): string {
    return base_url('shop.php') . '?' . http_build_query(array_merge($base, $overrides));
}

$priceRanges = [
    ['label' => 'Under ' . format_price(25000), 'min' => null, 'max' => 25000],
    ['label' => format_price(25000) . ' – ' . format_price(40000), 'min' => 25000, 'max' => 40000],
    ['label' => 'Over ' . format_price(40000), 'min' => 40000, 'max' => null],
];
$activePriceLabel = null;
foreach ($priceRanges as $range) {
    if ((float) ($range['min'] ?? 0) === (float) ($minPrice ?? 0) && (string) ($range['max'] ?? '') === (string) ($maxPrice ?? '')) {
        $activePriceLabel = $range['label'];
    }
}

$pageTitle = $activeCategory ? $activeCategory['name'] : ($search !== '' ? 'Search results' : 'Shop all shoes');
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container">
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / <?= htmlspecialchars($pageTitle) ?></div>

    <?php if (!$activeCategory && $search === ''): ?>
      <div class="category-tiles">
        <?php foreach ($categories as $cat): ?>
          <a href="<?= shop_filter_url($baseParams, ['category' => $cat['slug']]) ?>" class="category-tile" style="--accent:<?= htmlspecialchars($cat['accent_color']) ?>;">
            <img src="<?= htmlspecialchars(category_image_url($cat)) ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
            <div class="category-tile-label">
              <span><?= htmlspecialchars($cat['name']) ?></span>
              <span class="category-tile-count"><?= (int) ($counts[$cat['id']] ?? 0) ?> shoe<?= ($counts[$cat['id']] ?? 0) === 1 ? '' : 's' ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="shop-layout">
      <aside class="filter-box">
        <h4>Categories</h4>
        <div class="filter-list">
          <a href="<?= shop_filter_url($baseParams, ['category' => null]) ?>" class="<?= !$activeCategory ? 'active' : '' ?>">
            <span>All shoes</span><span><?= (int) $totalCount ?></span>
          </a>
          <?php foreach ($categories as $cat): ?>
            <a href="<?= shop_filter_url($baseParams, ['category' => $cat['slug']]) ?>" class="filter-list-cat <?= $activeCategory && $activeCategory['slug'] === $cat['slug'] ? 'active' : '' ?>">
              <img src="<?= htmlspecialchars(category_image_url($cat)) ?>" alt="" class="filter-list-cat-img">
              <span><?= htmlspecialchars($cat['name']) ?></span><span><?= (int) ($counts[$cat['id']] ?? 0) ?></span>
            </a>
          <?php endforeach; ?>
        </div>

        <h4 style="margin-top:26px;">Price</h4>
        <div class="filter-list">
          <a href="<?= shop_filter_url($baseParams, ['min_price' => null, 'max_price' => null]) ?>" class="<?= $activePriceLabel === null ? 'active' : '' ?>">
            <span>All prices</span>
          </a>
          <?php foreach ($priceRanges as $range): ?>
            <a href="<?= shop_filter_url($baseParams, ['min_price' => $range['min'], 'max_price' => $range['max']]) ?>" class="<?= $activePriceLabel === $range['label'] ? 'active' : '' ?>">
              <span><?= htmlspecialchars($range['label']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </aside>

      <div>
        <div class="results-bar">
          <div>
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
            <?php if ($search !== ''): ?><p class="results-count">Results for &ldquo;<?= htmlspecialchars($search) ?>&rdquo;</p><?php endif; ?>
          </div>
          <form method="get" action="<?= base_url('shop.php') ?>" style="display:flex;gap:10px;align-items:center;">
            <?php if ($activeCategory): ?><input type="hidden" name="category" value="<?= htmlspecialchars($activeCategory['slug']) ?>"><?php endif; ?>
            <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
            <?php if ($minPrice !== null): ?><input type="hidden" name="min_price" value="<?= htmlspecialchars((string) $minPrice) ?>"><?php endif; ?>
            <?php if ($maxPrice !== null): ?><input type="hidden" name="max_price" value="<?= htmlspecialchars((string) $maxPrice) ?>"><?php endif; ?>
            <label for="sort" style="margin:0;font-weight:500;color:var(--muted);font-size:0.85rem;">Sort</label>
            <select name="sort" id="sort" onchange="this.form.submit()" style="width:auto;padding:9px 12px;">
              <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
              <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
              <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
              <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A-Z</option>
              <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
            </select>
          </form>
        </div>

        <p class="results-count" style="margin-bottom:20px;"><?= count($products) ?> shoe<?= count($products) === 1 ? '' : 's' ?> found</p>

        <?php if ($products): ?>
          <div class="product-grid">
            <?php foreach ($products as $product): echo product_card_html($product, $wishlistIds); endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <h3>No shoes found</h3>
            <p>Try a different category, price range, or search term.</p>
            <a href="<?= base_url('shop.php') ?>" class="btn btn-outline btn-sm">Clear filters</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
