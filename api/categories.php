<?php
/**
 * GET /api/categories.php — list all categories with their active product count.
 */
require_once __DIR__ . '/bootstrap.php';

$rows = $pdo->query(
    "SELECT c.id, c.name, c.slug, c.accent_color, COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id AND p.status = 'active'
     GROUP BY c.id
     ORDER BY c.name"
)->fetchAll();

$categories = array_map(fn($c) => [
    'id' => (int) $c['id'],
    'name' => $c['name'],
    'slug' => $c['slug'],
    'accent_color' => $c['accent_color'],
    'product_count' => (int) $c['product_count'],
    'url' => api_absolute_url(base_url('shop.php?category=' . urlencode($c['slug']))),
], $rows);

api_success($categories);
