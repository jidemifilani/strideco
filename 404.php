<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

http_response_code(404);
$pageTitle = 'Page Not Found';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container result-page">
    <div class="result-icon fail">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="M21 21l-4.3-4.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    </div>
    <h1>Page not found</h1>
    <p>The page you're looking for doesn't exist or may have moved. Let's get you back on track.</p>
    <div class="hero-actions" style="justify-content:center;margin-top:8px;">
      <a href="<?= base_url('index.php') ?>" class="btn btn-primary">Back to home</a>
      <a href="<?= base_url('shop.php') ?>" class="btn btn-outline">Shop all shoes</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
