<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/paystack-client.php';
start_session_if_needed();

$reference = trim($_GET['reference'] ?? $_GET['trxref'] ?? '');
if ($reference === '') {
    redirect(base_url('index.php'));
}

$stmt = $pdo->prepare('SELECT * FROM orders WHERE order_ref = ?');
$stmt->execute([$reference]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'We could not find that order.');
    redirect(base_url('index.php'));
}

if ($order['payment_status'] === 'paid') {
    redirect(base_url('order-success.php?ref=' . urlencode($reference)));
}

try {
    $result = paystack_request('/transaction/verify/' . urlencode($reference), 'GET');
    $data = $result['data'] ?? [];
    $paidAmount = isset($data['amount']) ? ((int) $data['amount']) / 100 : 0;
    $expectedAmount = (float) $order['total'];
    $amountMatches = abs($paidAmount - $expectedAmount) < 0.5;

    if (!empty($result['status']) && ($data['status'] ?? '') === 'success' && $amountMatches) {
        // Idempotent: if the webhook already confirmed this order first, this
        // just returns false and skips straight to the success page.
        if (confirm_order_paid($pdo, $order, $data['reference'] ?? $reference)) {
            clear_cart();
        }
        redirect(base_url('order-success.php?ref=' . urlencode($reference)));
    }

    mark_order_payment_failed($pdo, (int) $order['id']);
    redirect(base_url('order-failed.php?ref=' . urlencode($reference)));
} catch (PaystackException $e) {
    set_flash('error', 'We could not confirm your payment: ' . $e->getMessage());
    redirect(base_url('order-failed.php?ref=' . urlencode($reference)));
}
