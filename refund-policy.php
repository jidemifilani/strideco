<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$contactEmail = get_setting($pdo, 'contact_email', 'hello@strideco.example');

$pageTitle = 'Refund & Returns Policy';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container" style="max-width:720px;">
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / Refund &amp; Returns Policy</div>
    <h1 style="margin-bottom:8px;">Refund &amp; Returns Policy</h1>
    <p class="text-muted" style="margin-bottom:32px;">Last updated: <?= date('d F Y') ?></p>

    <div style="line-height:1.7;">
      <p>We want you to be happy with your purchase. If something isn't right, here's how returns, exchanges, and refunds work at <?= htmlspecialchars($siteName) ?>.</p>

      <h3>1. Return window</h3>
      <p>You can request a return within <strong>5 days</strong>, or an exchange within <strong>7 days</strong>, of receiving your order, as long as the item is unworn, unwashed, and in its original packaging with tags attached.</p>

      <h3>2. How to start a return</h3>
      <p>Contact us at <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" style="color:var(--accent-dark);"><?= htmlspecialchars($contactEmail) ?></a> or via our <a href="<?= base_url('contact.php') ?>" style="color:var(--accent-dark);">contact form</a> with your order reference (find it on your <a href="<?= base_url('track-order.php') ?>" style="color:var(--accent-dark);">order tracking page</a>) and the reason for the return. We'll confirm eligibility and next steps within 2 business days.</p>

      <h3>3. Wrong size or defective items</h3>
      <p>If you received the wrong size, a defective pair, or an item different from what you ordered, we'll cover the cost of the exchange or refund — this is on us, not you.</p>

      <h3>4. Change of mind</h3>
      <p>For change-of-mind returns (you ordered the right item, but no longer want it), return shipping is the customer's responsibility unless we advise otherwise.</p>

      <h3>5. Refunds</h3>
      <p>Once we receive and inspect the returned item, approved refunds are issued back to your original Paystack payment method within 5–10 business days, depending on your bank's processing time. Shipping fees are non-refundable except where the return is due to our error (wrong/defective item).</p>

      <h3>6. Order cancellations before shipping</h3>
      <p>If your order hasn't shipped yet, contact us as soon as possible and we'll do our best to cancel and refund it in full. Once an order has shipped, it follows the standard return process above instead.</p>

      <h3>7. Non-returnable items</h3>
      <p>For hygiene reasons, worn shoes cannot be returned unless faulty or sent in error.</p>

      <h3>8. Contact us</h3>
      <p>Any questions about a return or refund — reach out any time at <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" style="color:var(--accent-dark);"><?= htmlspecialchars($contactEmail) ?></a>.</p>

      <p class="text-muted" style="margin-top:32px;font-size:0.85rem;">This is a general-purpose template and hasn't been reviewed by a lawyer. Have it checked against your specific business practices and applicable law before relying on it commercially.</p>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
