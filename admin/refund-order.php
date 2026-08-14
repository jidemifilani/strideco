<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../paystack/paystack-client.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = (int) ($_POST['id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        set_flash('error', 'Order not found.');
    } else {
        $result = process_refund($pdo, $order, $reason !== '' ? $reason : null);
        set_flash($result['ok'] ? 'success' : 'error', $result['message']);
    }

    redirect(base_url('admin/order-view.php?id=' . $id));
}

redirect(base_url('admin/orders.php'));
