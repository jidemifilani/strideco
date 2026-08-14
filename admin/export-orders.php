<?php
require_once __DIR__ . '/includes/auth.php';

$orders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="strideco-orders-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Order Ref', 'Customer', 'Email', 'Phone', 'Address', 'City', 'Subtotal', 'Discount', 'Shipping', 'Total', 'Coupon', 'Payment Status', 'Order Status', 'Date']);

foreach ($orders as $o) {
    fputcsv($out, [
        $o['order_ref'],
        $o['customer_name'],
        $o['email'],
        $o['phone'],
        $o['address'],
        $o['city'],
        $o['subtotal'],
        $o['discount_amount'],
        $o['shipping_fee'],
        $o['total'],
        $o['coupon_code'],
        $o['payment_status'],
        $o['order_status'],
        $o['created_at'],
    ]);
}

fclose($out);
exit;
