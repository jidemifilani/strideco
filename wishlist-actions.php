<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    redirect(base_url('index.php'));
}

$productId = (int) ($_POST['product_id'] ?? 0);
$redirectTo = $_POST['redirect_to'] ?? base_url('wishlist.php');
if (strpos($redirectTo, base_url()) !== 0) {
    $redirectTo = base_url('wishlist.php');
}

if ($productId > 0) {
    if (is_customer_logged_in()) {
        $customerId = current_customer_id();
        $stmt = $pdo->prepare('SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?');
        $stmt->execute([$customerId, $productId]);
        if ($stmt->fetch()) {
            $pdo->prepare('DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?')->execute([$customerId, $productId]);
        } else {
            $pdo->prepare('INSERT IGNORE INTO wishlists (customer_id, product_id) VALUES (?, ?)')->execute([$customerId, $productId]);
        }
    } else {
        if (in_array($productId, wishlist_session_ids(), true)) {
            remove_from_wishlist_session($productId);
        } else {
            add_to_wishlist_session($productId);
        }
    }
}

redirect($redirectTo);
