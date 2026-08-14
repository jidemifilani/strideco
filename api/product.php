<?php
/**
 * GET /api/product.php?slug=<slug> — full detail for one active product.
 */
require_once __DIR__ . '/bootstrap.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    api_error(400, 'Missing required "slug" parameter.');
}

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.accent_color
     FROM products p JOIN categories c ON p.category_id = c.id
     WHERE p.slug = ? AND p.status = 'active'"
);
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    api_error(404, 'Product not found.');
}

$sizesStmt = $pdo->prepare('SELECT size, stock FROM product_sizes WHERE product_id = ? ORDER BY CAST(size AS UNSIGNED)');
$sizesStmt->execute([$product['id']]);
$sizes = array_map(fn($s) => ['size' => $s['size'], 'stock' => (int) $s['stock']], $sizesStmt->fetchAll());

$variants = array_map(function ($v) use ($product) {
    return [
        'color_name' => $v['color_name'],
        'color_hex' => $v['color_hex'],
        'image_url' => api_absolute_url(variant_image_url($v, $product)),
        'sizes' => array_map(fn($size, $stock) => ['size' => $size, 'stock' => $stock], array_keys($v['sizes']), array_values($v['sizes'])),
    ];
}, get_product_variants($pdo, (int) $product['id']));

$galleryStmt = $pdo->prepare('SELECT image, media_type FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
$galleryStmt->execute([$product['id']]);
$gallery = array_map(fn($g) => [
    'url' => api_absolute_url(base_url('assets/uploads/' . $g['image'])),
    'type' => $g['media_type'],
], $galleryStmt->fetchAll());

$featureLines = array_values(array_filter(array_map('trim', explode("\n", $product['features'] ?? ''))));
$rating = get_rating_summary($pdo, (int) $product['id']);

$detail = api_product_summary($product);
$detail['description'] = $product['description'];
$detail['features'] = $featureLines;
$detail['color'] = $product['color'];
$detail['sizes'] = $sizes;
$detail['variants'] = $variants;
$detail['gallery'] = $gallery;
$detail['rating'] = ['average' => $rating['avg'], 'count' => $rating['count']];

api_success($detail);
