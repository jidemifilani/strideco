<?php
require_once __DIR__ . '/includes/auth.php';
require_super_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id === (int) $_SESSION['admin_id']) {
        set_flash('error', "You can't remove your own account.");
    } else {
        $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]);
        set_flash('success', 'Admin user removed.');
    }
}

redirect(base_url('admin/admins.php'));
