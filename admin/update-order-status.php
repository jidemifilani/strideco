<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['order_status'] ?? '';
    $note = trim($_POST['note'] ?? '');
    if (in_array($status, ['processing', 'shipped', 'delivered', 'cancelled'], true)) {
        $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?')->execute([$status, $id]);
        log_order_status($pdo, $id, $status, $note !== '' ? $note : null);

        $orderStmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $orderStmt->execute([$id]);
        $orderRow = $orderStmt->fetch();
        if ($orderRow) {
            send_order_status_email($pdo, $orderRow, $status, $note !== '' ? $note : null);
        }

        set_flash('success', 'Order status updated.');
    }
    redirect(base_url('admin/order-view.php?id=' . $id));
}

redirect(base_url('admin/orders.php'));
