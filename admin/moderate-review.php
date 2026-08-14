<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (in_array($status, ['approved', 'rejected'], true)) {
        $pdo->prepare('UPDATE reviews SET status = ? WHERE id = ?')->execute([$status, $id]);
        set_flash('success', 'Review ' . $status . '.');
    }
}

redirect(base_url('admin/reviews.php'));
