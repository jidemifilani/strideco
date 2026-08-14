<?php
require_once __DIR__ . '/../includes/functions.php';

class PaystackException extends Exception {}

function paystack_configured(): bool {
    return strpos(PAYSTACK_SECRET_KEY, 'xxxx') === false;
}

/**
 * @throws PaystackException
 */
function paystack_request(string $endpoint, string $method = 'GET', ?array $data = null): array {
    $ch = curl_init('https://api.paystack.co' . $endpoint);
    $headers = [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
    ];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new PaystackException('Could not reach Paystack: ' . $error);
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new PaystackException('Unexpected response from Paystack.');
    }
    if ($httpCode >= 400 && empty($decoded['status'])) {
        throw new PaystackException($decoded['message'] ?? 'Paystack request failed.');
    }
    return $decoded;
}

/**
 * Marks an order paid, decrements stock, and logs the timeline entry.
 * Shared by the browser-redirect callback AND the server-to-server webhook,
 * so payment confirmation happens the same way regardless of which one fires
 * first — the `payment_status = 'pending'` guard makes this safe to call from
 * both without double-decrementing stock.
 *
 * @return bool true if this call actually applied the update (order was still pending)
 */
function confirm_order_paid(PDO $pdo, array $order, string $paystackReference): bool {
    $update = $pdo->prepare(
        "UPDATE orders SET payment_status = 'paid', payment_reference = ? WHERE id = ? AND payment_status = 'pending'"
    );
    $update->execute([$paystackReference, $order['id']]);

    if ($update->rowCount() === 0) {
        return false;
    }

    $itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $itemsStmt->execute([$order['id']]);
    $decrementProductStmt = $pdo->prepare(
        'UPDATE product_sizes SET stock = GREATEST(stock - ?, 0) WHERE product_id = ? AND size = ?'
    );
    $decrementVariantStmt = $pdo->prepare(
        'UPDATE variant_sizes SET stock = GREATEST(stock - ?, 0) WHERE variant_id = ? AND size = ?'
    );
    foreach ($itemsStmt->fetchAll() as $item) {
        if (!empty($item['variant_id'])) {
            $decrementVariantStmt->execute([$item['quantity'], $item['variant_id'], $item['size']]);
        } elseif ($item['product_id']) {
            $decrementProductStmt->execute([$item['quantity'], $item['product_id'], $item['size']]);
        }
    }

    log_order_status($pdo, (int) $order['id'], 'paid');
    send_order_confirmation_email($pdo, $order);
    return true;
}

function mark_order_payment_failed(PDO $pdo, int $orderId): void {
    $update = $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ? AND payment_status = 'pending'");
    $update->execute([$orderId]);
    if ($update->rowCount() > 0) {
        log_order_status($pdo, $orderId, 'payment_failed');
    }
}

/**
 * Refunds a paid order in full. If Paystack is configured (real API keys),
 * calls their /refund endpoint — Paystack processes refunds asynchronously,
 * so a 200 here means "refund request accepted", not "money already back in
 * the customer's account"; a production build would ideally also listen for
 * the `refund.processed` webhook event to confirm completion, the same way
 * payment confirmation is double-checked via paystack/webhook.php. If
 * Paystack isn't configured, the refund is still recorded locally (e.g. for
 * a shop owner refunding by bank transfer outside Paystack) — this mirrors
 * how the rest of the app treats an unconfigured Paystack: don't block the
 * admin from doing their job, just be honest about what actually happened.
 *
 * @return array{ok: bool, message: string}
 */
function process_refund(PDO $pdo, array $order, ?string $reason = null): array {
    if ((string) $order['payment_status'] !== 'paid') {
        return ['ok' => false, 'message' => 'Only paid orders can be refunded.'];
    }

    $refundReference = null;
    if (paystack_configured() && !empty($order['payment_reference'])) {
        try {
            $response = paystack_request('/refund', 'POST', ['transaction' => $order['payment_reference']]);
            if (empty($response['status'])) {
                return ['ok' => false, 'message' => $response['message'] ?? 'Paystack declined the refund request.'];
            }
            $refundReference = $response['data']['id'] ?? $response['data']['reference'] ?? null;
        } catch (PaystackException $e) {
            return ['ok' => false, 'message' => 'Could not reach Paystack: ' . $e->getMessage()];
        }
    }

    $update = $pdo->prepare(
        "UPDATE orders SET payment_status = 'refunded', refund_amount = ?, refund_reference = ?, refunded_at = NOW()
         WHERE id = ? AND payment_status = 'paid'"
    );
    $update->execute([$order['total'], $refundReference, $order['id']]);

    if ($update->rowCount() === 0) {
        return ['ok' => false, 'message' => 'This order was already refunded or is no longer eligible.'];
    }

    $note = $refundReference ? "Refunded via Paystack (ref: {$refundReference})" : 'Refunded manually (Paystack not configured or no payment reference on file)';
    if ($reason) { $note .= ' — ' . $reason; }
    log_order_status($pdo, (int) $order['id'], 'refunded', $note);
    send_order_refunded_email($pdo, $order, (float) $order['total'], $reason);

    return ['ok' => true, 'message' => 'Refund processed.'];
}
