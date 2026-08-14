<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = (int) ($_POST['id'] ?? 0);
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    set_flash('success', 'Product deleted.');
}

redirect(base_url('admin/products.php'));
