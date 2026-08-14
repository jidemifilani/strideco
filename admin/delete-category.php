<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = (int) ($_POST['id'] ?? 0);
    try {
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        set_flash('success', 'Category deleted.');
    } catch (PDOException $e) {
        set_flash('error', 'This category still has products in it — move or delete those first.');
    }
}

redirect(base_url('admin/categories.php'));
