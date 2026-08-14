<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    redirect(base_url('checkout.php'));
}

$action = $_POST['action'] ?? '';

if ($action === 'remove') {
    unset($_SESSION['gift_card_code']);
    redirect(base_url('checkout.php'));
}

if ($action === 'apply') {
    $code = trim($_POST['gift_card_code'] ?? '');
    $result = validate_gift_card($pdo, $code);

    if ($result['valid']) {
        $_SESSION['gift_card_code'] = $result['giftCard']['code'];
        set_flash('success', 'Gift card applied — ' . format_price((float) $result['giftCard']['balance']) . ' available.');
    } else {
        unset($_SESSION['gift_card_code']);
        set_flash('error', $result['message']);
    }
}

redirect(base_url('checkout.php'));
