<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/paystack/paystack-client.php';
start_session_if_needed();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf() || honeypot_triggered()) {
    redirect(base_url('checkout.php'));
}

$items = get_cart_details($pdo);
if (!$items) {
    set_flash('error', 'Your cart is empty.');
    redirect(base_url('cart.php'));
}

$name = trim($_POST['customer_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');

$errors = [];
if ($name === '') { $errors[] = 'Full name is required.'; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid email is required.'; }
if ($phone === '') { $errors[] = 'Phone number is required.'; }
if ($address === '') { $errors[] = 'Delivery address is required.'; }
if ($city === '') { $errors[] = 'City is required.'; }

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect(base_url('checkout.php'));
}

$subtotal = 0;
foreach ($items as $line) { $subtotal += $line['line_total']; }
$shippingResult = resolve_shipping_fee($pdo, $city);
$shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : $shippingResult['fee'];

$couponCode = null;
$discount = 0.0;
if (!empty($_SESSION['coupon_code'])) {
    $couponResult = validate_coupon($pdo, $_SESSION['coupon_code'], $subtotal);
    if ($couponResult['valid']) {
        $couponCode = $couponResult['coupon']['code'];
        $discount = $couponResult['discount'];
    }
}
$tax = calculate_tax($pdo, max(0, $subtotal - $discount));
$totalBeforeGiftCard = max(0, $subtotal - $discount + $shipping + $tax);

$giftCard = null;
$giftCardAmount = 0.0;
if (!empty($_SESSION['gift_card_code'])) {
    $giftCardResult = validate_gift_card($pdo, $_SESSION['gift_card_code']);
    if ($giftCardResult['valid']) {
        $giftCard = $giftCardResult['giftCard'];
        $giftCardAmount = min((float) $giftCard['balance'], $totalBeforeGiftCard);
    }
}
$total = max(0, $totalBeforeGiftCard - $giftCardAmount);
$customerId = is_customer_logged_in() ? current_customer_id() : null;

do {
    $orderRef = generate_order_ref();
    $check = $pdo->prepare('SELECT id FROM orders WHERE order_ref = ?');
    $check->execute([$orderRef]);
} while ($check->fetch());

try {
    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare(
        'INSERT INTO orders (customer_id, order_ref, customer_name, email, phone, address, city, subtotal, shipping_fee, coupon_code, discount_amount, gift_card_code, gift_card_amount, tax_amount, total, payment_status, order_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", "processing")'
    );
    $orderStmt->execute([$customerId, $orderRef, $name, $email, $phone, $address, $city, $subtotal, $shipping, $couponCode, $discount, $giftCard['code'] ?? null, $giftCardAmount, $tax, $total]);
    $orderId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, variant_id, variant_color, product_name, size, quantity, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($items as $line) {
        $itemStmt->execute([
            $orderId,
            $line['product']['id'],
            $line['variant']['id'] ?? null,
            $line['variant']['color_name'] ?? null,
            $line['product']['name'],
            $line['size'],
            $line['qty'],
            $line['product']['price'],
        ]);
    }

    if ($couponCode) {
        $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE code = ?')->execute([$couponCode]);
    }

    if ($giftCard && $giftCardAmount > 0) {
        // Guarded by "balance >= amount" so two concurrent orders redeeming
        // the same card can never combine to over-draft it — InnoDB row
        // locking serializes these within the transaction.
        $redeemStmt = $pdo->prepare('UPDATE gift_cards SET balance = balance - ? WHERE id = ? AND balance >= ?');
        $redeemStmt->execute([$giftCardAmount, $giftCard['id'], $giftCardAmount]);
        if ($redeemStmt->rowCount() === 0) {
            throw new Exception('Gift card balance changed before it could be redeemed.');
        }
        $pdo->prepare('INSERT INTO gift_card_redemptions (gift_card_id, order_id, amount_used) VALUES (?, ?, ?)')
            ->execute([$giftCard['id'], $orderId, $giftCardAmount]);
    }

    log_order_status($pdo, $orderId, 'order_placed');

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('error', 'Something went wrong placing your order. Please try again.');
    redirect(base_url('checkout.php'));
}

unset($_SESSION['coupon_code']);
unset($_SESSION['gift_card_code']);
mark_abandoned_cart_recovered($pdo, $email);

if ($total <= 0) {
    // Fully covered by gift card — nothing to charge, so skip Paystack
    // entirely and confirm the order the same way a successful payment would.
    $orderForConfirm = ['id' => $orderId, 'customer_name' => $name, 'order_ref' => $orderRef, 'email' => $email, 'total' => $total];
    confirm_order_paid($pdo, $orderForConfirm, 'GIFTCARD-' . $orderRef);
    clear_cart();
    redirect(base_url('order-success.php?ref=' . urlencode($orderRef)));
}

redirect(base_url('paystack/initialize.php?order_ref=' . urlencode($orderRef)));
