<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    redirect(base_url('checkout.php'));
}

$action = $_POST['action'] ?? '';

if ($action === 'remove') {
    unset($_SESSION['coupon_code']);
    redirect(base_url('checkout.php'));
}

if ($action === 'apply') {
    $code = trim($_POST['code'] ?? '');
    $subtotal = cart_subtotal($pdo);
    $result = validate_coupon($pdo, $code, $subtotal);

    if ($result['valid']) {
        $_SESSION['coupon_code'] = $result['coupon']['code'];
        set_flash('success', 'Coupon applied — you saved ' . format_price($result['discount']) . '.');
    } else {
        unset($_SESSION['coupon_code']);
        set_flash('error', $result['message']);
    }
}

redirect(base_url('checkout.php'));
