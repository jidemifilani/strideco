<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = (int) ($_POST['id'] ?? 0);
    $productId = (int) ($_POST['product_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT * FROM product_variants WHERE id = ? AND product_id = ?');
    $stmt->execute([$id, $productId]);
    $variant = $stmt->fetch();

    if ($variant) {
        if (!empty($variant['image'])) {
            $path = __DIR__ . '/../assets/uploads/' . $variant['image'];
            if (is_file($path)) {
                unlink($path);
            }
        }
        $pdo->prepare('DELETE FROM product_variants WHERE id = ?')->execute([$id]);
        set_flash('success', 'Color variant removed.');
    }
}

redirect(base_url('admin/product-form.php?id=' . (int) $productId));
