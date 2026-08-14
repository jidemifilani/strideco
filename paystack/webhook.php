<?php
/**
 * Paystack webhook — server-to-server payment confirmation.
 *
 * Automates order confirmation independently of the customer's browser: the
 * redirect-based callback.php only fires if the shopper's browser makes it
 * back to your site after paying (they might close the tab, lose signal,
 * etc). Paystack calls this endpoint directly from their servers the moment
 * a payment succeeds, so the order gets confirmed either way — whichever of
 * the two fires first "wins" and the other is a safe no-op, thanks to the
 * `payment_status = 'pending'` guard in confirm_order_paid().
 *
 * To activate: in your Paystack dashboard, go to Settings → API Keys &
 * Webhooks, and set the webhook URL to:
 *   https://yourdomain.com/strideco/paystack/webhook.php
 * This only works on a real public domain — Paystack's servers cannot reach
 * a localhost address, so this endpoint is inert (but harmless) until deployed.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/paystack-client.php';

header('Content-Type: application/json');

$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

if (!paystack_configured() || $rawBody === '' || $signature === '') {
    http_response_code(400);
    echo json_encode(['status' => false]);
    exit;
}

$expectedSignature = hash_hmac('sha512', $rawBody, PAYSTACK_SECRET_KEY);
if (!hash_equals($expectedSignature, $signature)) {
    // Not actually from Paystack — never trust an unsigned/mis-signed payload.
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Invalid signature']);
    exit;
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['status' => false]);
    exit;
}

$event = $payload['event'] ?? '';
$data = $payload['data'] ?? [];
$reference = $data['reference'] ?? '';

if ($event === 'charge.success' && $reference !== '') {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_ref = ?');
    $stmt->execute([$reference]);
    $order = $stmt->fetch();

    if ($order && $order['payment_status'] === 'pending') {
        $paidAmount = isset($data['amount']) ? ((int) $data['amount']) / 100 : 0;
        $amountMatches = abs($paidAmount - (float) $order['total']) < 0.5;
        if (($data['status'] ?? '') === 'success' && $amountMatches) {
            confirm_order_paid($pdo, $order, $data['reference']);
        }
    }
}

// Always 200 quickly so Paystack doesn't retry unnecessarily — any real
// problem above already exited with its own status code before this point.
http_response_code(200);
echo json_encode(['status' => true]);
