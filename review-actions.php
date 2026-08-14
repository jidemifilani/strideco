<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf() || honeypot_triggered()) {
    redirect(base_url('index.php'));
}

$productId = (int) ($_POST['product_id'] ?? 0);
$slug = trim($_POST['slug'] ?? '');
$rating = max(1, min(5, (int) ($_POST['rating'] ?? 0)));
$comment = trim($_POST['comment'] ?? '');
$name = is_customer_logged_in() ? ($_SESSION['customer_name'] ?? 'Customer') : trim($_POST['customer_name'] ?? '');

$redirectUrl = base_url('product.php?slug=' . urlencode($slug));

$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND status = 'active'");
$stmt->execute([$productId]);
if (!$stmt->fetch()) {
    redirect(base_url('shop.php'));
}

if ($name === '' || $comment === '' || $rating < 1) {
    set_flash('error', 'Please add your name, a rating, and a comment.');
    redirect($redirectUrl);
}

$stmt = $pdo->prepare(
    'INSERT INTO reviews (product_id, customer_id, customer_name, rating, comment, status) VALUES (?, ?, ?, ?, ?, "pending")'
);
$stmt->execute([$productId, current_customer_id(), $name, $rating, $comment]);

set_flash('success', "Thanks for your review! It'll appear once our team approves it.");
redirect($redirectUrl);
