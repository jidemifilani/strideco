<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$contactEmail = get_setting($pdo, 'contact_email', 'hello@strideco.example');

$pageTitle = 'Terms of Service';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container" style="max-width:720px;">
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / Terms of Service</div>
    <h1 style="margin-bottom:8px;">Terms of Service</h1>
    <p class="text-muted" style="margin-bottom:32px;">Last updated: <?= date('d F Y') ?></p>

    <div style="line-height:1.7;">
      <p>These Terms of Service govern your use of <?= htmlspecialchars($siteName) ?> and any purchase you make with us. By placing an order or creating an account, you agree to these terms.</p>

      <h3>1. Products and pricing</h3>
      <p>We make reasonable efforts to display accurate product descriptions, images, and prices. Prices are listed in Nigerian Naira (₦) and include applicable taxes unless stated otherwise. We reserve the right to correct pricing errors and to limit order quantities.</p>

      <h3>2. Orders and payment</h3>
      <p>An order is confirmed once payment is successfully processed through Paystack. We reserve the right to cancel or refuse any order, including in cases of suspected fraud, pricing errors, or stock unavailability — if this happens after payment, we'll issue a full refund.</p>

      <h3>3. Shipping and delivery</h3>
      <p>Delivery timeframes shown at checkout are estimates, not guarantees. Risk of loss and title for items pass to you once the order is handed to the delivery courier.</p>

      <h3>4. Returns, exchanges, and cancellations</h3>
      <p>See our <a href="<?= base_url('refund-policy.php') ?>" style="color:var(--accent-dark);">Refund &amp; Returns Policy</a> for the full process and conditions.</p>

      <h3>5. Account responsibilities</h3>
      <p>You're responsible for keeping your account password confidential and for all activity under your account. Let us know immediately if you suspect unauthorized use.</p>

      <h3>6. Reviews and user content</h3>
      <p>Product reviews you submit must be honest and based on genuine experience with the product. We review submissions before they go live and may decline to publish reviews that are abusive, spam, or unrelated to the product.</p>

      <h3>7. Limitation of liability</h3>
      <p>To the extent permitted by law, <?= htmlspecialchars($siteName) ?> is not liable for indirect or consequential losses arising from use of this site or its products, beyond the value of the order in question.</p>

      <h3>8. Changes to these terms</h3>
      <p>We may update these terms from time to time; the "last updated" date above reflects the latest revision. Continued use of the site after changes means you accept the updated terms.</p>

      <h3>9. Governing law</h3>
      <p>These terms are governed by the laws of the Federal Republic of Nigeria.</p>

      <h3>10. Contact us</h3>
      <p>Questions about these terms? Reach us at <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" style="color:var(--accent-dark);"><?= htmlspecialchars($contactEmail) ?></a>.</p>

      <p class="text-muted" style="margin-top:32px;font-size:0.85rem;">This is a general-purpose template and hasn't been reviewed by a lawyer. Have it checked against your specific business practices and applicable law before relying on it commercially.</p>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
