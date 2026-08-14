<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

$ids = get_wishlist_ids($pdo);
$products = [];
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.accent_color,
           (SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id AND r.status = 'approved') AS rating_avg,
           (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.status = 'approved') AS rating_count
         FROM products p JOIN categories c ON p.category_id = c.id
         WHERE p.id IN ($placeholders) AND p.status = 'active'
         ORDER BY p.name"
    );
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
}

$pageTitle = 'Wishlist';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container">
    <?php if (is_customer_logged_in()): ?>
      <h1 style="margin-bottom:20px;">My Account</h1>
      <nav class="account-tabs">
        <a href="<?= base_url('account/index.php') ?>">Profile</a>
        <a href="<?= base_url('account/orders.php') ?>">Orders</a>
        <a href="<?= base_url('wishlist.php') ?>" class="active">Wishlist (<?= count($products) ?>)</a>
        <a href="<?= base_url('account/logout.php') ?>">Log out</a>
      </nav>
    <?php else: ?>
      <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / Wishlist</div>
      <h1 style="margin-bottom:28px;">Your Wishlist</h1>
    <?php endif; ?>

    <?php if (!$products): ?>
      <div class="empty-state">
        <h3>Your wishlist is empty</h3>
        <p>Tap the heart on any shoe to save it here for later.</p>
        <a href="<?= base_url('shop.php') ?>" class="btn btn-primary btn-sm">Browse shoes</a>
      </div>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($products as $product): echo product_card_html($product, $ids); endforeach; ?>
      </div>
      <?php if (!is_customer_logged_in()): ?>
        <p class="text-muted" style="margin-top:28px;">
          <a href="<?= base_url('account/register.php') ?>" style="color:var(--accent-dark);font-weight:600;">Create an account</a>
          to keep your wishlist saved across devices.
        </p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
