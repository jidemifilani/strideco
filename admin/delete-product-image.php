<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = (int) ($_POST['id'] ?? 0);
    $productId = (int) ($_POST['product_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT * FROM product_images WHERE id = ?');
    $stmt->execute([$id]);
    $image = $stmt->fetch();

    if ($image) {
        $path = __DIR__ . '/../assets/uploads/' . $image['image'];
        if (is_file($path)) {
            unlink($path);
        }
        $pdo->prepare('DELETE FROM product_images WHERE id = ?')->execute([$id]);
        set_flash('success', 'Photo removed.');
    }
}

redirect(base_url('admin/product-form.php?id=' . (int) $productId));
