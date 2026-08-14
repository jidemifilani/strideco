<?php
/**
 * GET /api/products.php — list active products.
 * Query params: category=<slug>, search=<term>, page=<n>, per_page=<n, max 50>
 */
require_once __DIR__ . '/bootstrap.php';

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));
$offset = ($page - 1) * $perPage;

$where = ["p.status = 'active'"];
$params = [];

$categorySlug = trim($_GET['category'] ?? '');
if ($categorySlug !== '') {
    $where[] = 'c.slug = ?';
    $params[] = $categorySlug;
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.id WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.accent_color
     FROM products p JOIN categories c ON p.category_id = c.id
     WHERE {$whereSql}
     ORDER BY p.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$products = array_map('api_product_summary', $stmt->fetchAll());

api_success($products, [
    'page' => $page,
    'per_page' => $perPage,
    'total' => $total,
    'total_pages' => (int) ceil($total / $perPage),
]);
