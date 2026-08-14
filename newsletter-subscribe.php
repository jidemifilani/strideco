<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

$redirectTo = $_POST['redirect_to'] ?? base_url('index.php');
if (strpos($redirectTo, base_url()) !== 0) {
    $redirectTo = base_url('index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf() || honeypot_triggered()) {
    redirect($redirectTo);
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Enter a valid email address to subscribe.');
} else {
    $stmt = $pdo->prepare('INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)');
    $stmt->execute([$email]);
    set_flash('success', "You're subscribed! Keep an eye on your inbox for new drops.");
}

redirect($redirectTo);
