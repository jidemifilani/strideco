<?php
/**
 * Shared bootstrap for the public read-only REST API (api/*.php). This API
 * exposes only what's already public on the storefront (active products,
 * categories, stock levels, reviews) — no customer data, no write endpoints,
 * so it's intentionally unauthenticated, same trust level as the website
 * itself. Useful for a future mobile app or other client — see README.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Only GET requests are supported.']);
    exit;
}

function api_success($data, array $meta = []): void {
    $body = ['ok' => true, 'data' => $data];
    if ($meta) { $body['meta'] = $meta; }
    echo json_encode($body);
    exit;
}

function api_error(int $httpCode, string $message): void {
    http_response_code($httpCode);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

/**
 * product_image_url()/base_url() already return a site-relative path
 * (e.g. "/strideco/assets/uploads/x.jpg") — this just adds the scheme+host
 * so API clients (which aren't browsing the site, so can't resolve a
 * relative URL against it) get a fully qualified link.
 */
function api_absolute_url(string $siteRelativePath): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . $siteRelativePath;
}

/**
 * True if any size (of any color variant, or the base sizes if the product
 * has no variants) has stock, OR the product is in pre-order mode (which
 * bypasses stock entirely by design — see database/upgrade_v4.sql). Mirrors
 * the same rules product.php uses, so the API and the storefront never
 * disagree on whether something is buyable.
 */
function api_product_in_stock(PDO $pdo, int $productId, bool $isPreorder): bool {
    if ($isPreorder) {
        return true;
    }
    $variants = get_product_variants($pdo, $productId);
    if ($variants) {
        foreach ($variants as $variant) {
            if (array_sum($variant['sizes']) > 0) { return true; }
        }
        return false;
    }
    $stmt = $pdo->prepare('SELECT SUM(stock) FROM product_sizes WHERE product_id = ?');
    $stmt->execute([$productId]);
    return ((int) $stmt->fetchColumn()) > 0;
}

function api_product_summary(array $product): array {
    global $pdo;
    $rating = get_rating_summary($pdo, (int) $product['id']);
    $isPreorder = !empty($product['is_preorder']);

    return [
        'id' => (int) $product['id'],
        'name' => $product['name'],
        'slug' => $product['slug'],
        'price' => (float) $product['price'],
        'currency' => 'NGN',
        'category' => [
            'name' => $product['category_name'] ?? null,
            'slug' => $product['category_slug'] ?? null,
        ],
        'image_url' => api_absolute_url(product_image_url($product)),
        'rating' => ['average' => $rating['avg'], 'count' => $rating['count']],
        'in_stock' => api_product_in_stock($pdo, (int) $product['id'], $isPreorder),
        'is_featured' => (bool) $product['is_featured'],
        'is_preorder' => $isPreorder,
        'preorder_available_at' => $product['preorder_available_at'] ?? null,
        'url' => api_absolute_url(base_url('product.php?slug=' . urlencode($product['slug']))),
    ];
}
