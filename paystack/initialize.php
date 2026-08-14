<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/paystack-client.php';
start_session_if_needed();

$orderRef = trim($_GET['order_ref'] ?? '');
$stmt = $pdo->prepare('SELECT * FROM orders WHERE order_ref = ?');
$stmt->execute([$orderRef]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'We could not find that order.');
    redirect(base_url('cart.php'));
}

if ($order['payment_status'] === 'paid') {
    redirect(base_url('order-success.php?ref=' . urlencode($orderRef)));
}

if (!paystack_configured()) {
    $pageTitle = 'Payment setup required';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <section class="section"><div class="container result-page">
      <div class="result-icon fail">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
      </div>
      <h1>Payment isn't set up yet</h1>
      <p>This store's owner needs to add live Paystack API keys in <code>config/config.php</code> before checkout can accept payment. Your order <strong><?= htmlspecialchars($orderRef) ?></strong> has been saved and is waiting to be paid.</p>
      <a href="<?= base_url('shop.php') ?>" class="btn btn-outline">Back to shop</a>
    </div></section>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

try {
    $result = paystack_request('/transaction/initialize', 'POST', [
        'email' => $order['email'],
        'amount' => (int) round(((float) $order['total']) * 100),
        'reference' => $order['order_ref'],
        'currency' => 'NGN',
        'callback_url' => full_base_url('paystack/callback.php'),
        'metadata' => [
            'order_ref' => $order['order_ref'],
            'customer_name' => $order['customer_name'],
        ],
    ]);

    if (!empty($result['status']) && !empty($result['data']['authorization_url'])) {
        redirect($result['data']['authorization_url']);
    }

    throw new PaystackException($result['message'] ?? 'Could not start payment.');
} catch (PaystackException $e) {
    set_flash('error', 'Payment could not be started: ' . $e->getMessage());
    redirect(base_url('checkout.php'));
}
