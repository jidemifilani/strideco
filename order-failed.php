<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

$ref = trim($_GET['ref'] ?? '');

$pageTitle = 'Payment failed';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container result-page">
    <div class="result-icon fail">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
    </div>
    <h1>Payment didn't go through</h1>
    <p>Your payment for order <strong><?= htmlspecialchars($ref) ?></strong> was not completed, so nothing has been charged. Your cart items are still saved — you can try again.</p>
    <a href="<?= base_url('cart.php') ?>" class="btn btn-primary">Return to cart</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
