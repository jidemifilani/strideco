<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$staticPages = [
    ['loc' => 'index.php', 'priority' => '1.0'],
    ['loc' => 'shop.php', 'priority' => '0.9'],
    ['loc' => 'about.php', 'priority' => '0.5'],
    ['loc' => 'faq.php', 'priority' => '0.5'],
    ['loc' => 'contact.php', 'priority' => '0.5'],
    ['loc' => 'track-order.php', 'priority' => '0.4'],
];

$categories = $pdo->query('SELECT slug FROM categories')->fetchAll();
$products = $pdo->query("SELECT slug, created_at FROM products WHERE status = 'active'")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <?php foreach ($staticPages as $p): ?>
  <url>
    <loc><?= htmlspecialchars(full_base_url($p['loc'])) ?></loc>
    <priority><?= $p['priority'] ?></priority>
  </url>
  <?php endforeach; ?>
  <?php foreach ($categories as $c): ?>
  <url>
    <loc><?= htmlspecialchars(full_base_url('shop.php?category=' . urlencode($c['slug']))) ?></loc>
    <priority>0.7</priority>
  </url>
  <?php endforeach; ?>
  <?php foreach ($products as $p): ?>
  <url>
    <loc><?= htmlspecialchars(full_base_url('product.php?slug=' . urlencode($p['slug']))) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($p['created_at'])) ?></lastmod>
    <priority>0.8</priority>
  </url>
  <?php endforeach; ?>
</urlset>
