<?php
/**
 * Fire-and-forget AJAX endpoint: called when the customer's cursor leaves
 * the checkout email field, so we have a snapshot + contact point to send a
 * recovery reminder to if they never finish placing the order. Always
 * responds 204 regardless of outcome — this must never interrupt checkout.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

http_response_code(204);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    exit;
}

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit;
}

$items = get_cart_details($pdo);
if (!$items) {
    exit;
}

$subtotal = 0.0;
foreach ($items as $line) { $subtotal += $line['line_total']; }

capture_abandoned_cart($pdo, $email, $items, $subtotal);
