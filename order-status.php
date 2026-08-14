<?php
/**
 * Lightweight JSON endpoint powering the "live" auto-refresh on the order
 * tracking page — polled every ~20s so a status change made in the admin
 * panel shows up on a customer's open tracking page without them reloading.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$ref = trim($_GET['ref'] ?? '');
$email = trim($_GET['email'] ?? '');

if ($ref === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM orders WHERE order_ref = ? AND LOWER(email) = LOWER(?)');
$stmt->execute([$ref, $email]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['ok' => false]);
    exit;
}

$timeline = get_order_timeline($pdo, (int) $order['id']);

echo json_encode([
    'ok' => true,
    'payment_status' => $order['payment_status'],
    'order_status' => $order['order_status'],
    'timeline_html' => order_timeline_html($timeline, $order['payment_status']),
    'updated_at' => date('H:i:s'),
]);
