<?php
/**
 * JSON endpoint polled by checkout.php's city field: recomputes shipping fee
 * (zone-based), tax, and total as the customer types their delivery city.
 * Recomputes subtotal/discount from the server-side cart session rather than
 * trusting client input — this is only a live preview, but there's no reason
 * to trust the browser for numbers we can derive ourselves.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

header('Content-Type: application/json');

$items = get_cart_details($pdo);
$subtotal = 0.0;
foreach ($items as $line) { $subtotal += $line['line_total']; }

$discount = 0.0;
if (!empty($_SESSION['coupon_code'])) {
    $couponResult = validate_coupon($pdo, $_SESSION['coupon_code'], $subtotal);
    if ($couponResult['valid']) {
        $discount = $couponResult['discount'];
    }
}

$city = trim($_GET['city'] ?? '');
$shippingResult = resolve_shipping_fee($pdo, $city);
$shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0.0 : $shippingResult['fee'];
$tax = calculate_tax($pdo, max(0, $subtotal - $discount));
$totalBeforeGiftCard = max(0, $subtotal - $discount + $shipping + $tax);

$giftCardAmount = 0.0;
if (!empty($_SESSION['gift_card_code'])) {
    $giftCardResult = validate_gift_card($pdo, $_SESSION['gift_card_code']);
    if ($giftCardResult['valid']) {
        $giftCardAmount = min((float) $giftCardResult['giftCard']['balance'], $totalBeforeGiftCard);
    }
}
$total = max(0, $totalBeforeGiftCard - $giftCardAmount);

echo json_encode([
    'ok' => true,
    'shipping' => $shipping,
    'shipping_label' => $shipping > 0 ? format_price($shipping) : 'Free',
    'zone_name' => $shippingResult['zone']['name'] ?? null,
    'tax' => $tax,
    'tax_rate_percent' => get_tax_rate_percent($pdo),
    'total' => $total,
    'total_label' => format_price($total),
]);
