<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = (int) ($_POST['id'] ?? 0);
    $pdo->prepare('DELETE FROM shipping_zones WHERE id = ?')->execute([$id]);

    // If that was the default zone, promote another one so checkout always
    // has a catch-all rather than silently falling back to SHIPPING_FEE.
    $hasDefault = (bool) $pdo->query('SELECT COUNT(*) FROM shipping_zones WHERE is_default = 1')->fetchColumn();
    if (!$hasDefault) {
        $pdo->query('UPDATE shipping_zones SET is_default = 1 ORDER BY id ASC LIMIT 1');
    }

    set_flash('success', 'Shipping zone deleted.');
}

redirect(base_url('admin/shipping-zones.php'));
