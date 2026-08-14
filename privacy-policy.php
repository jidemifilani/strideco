<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$contactEmail = get_setting($pdo, 'contact_email', 'hello@strideco.example');
$contactAddress = get_setting($pdo, 'contact_address', 'Lagos, Nigeria');

$pageTitle = 'Privacy Policy';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container" style="max-width:720px;">
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / Privacy Policy</div>
    <h1 style="margin-bottom:8px;">Privacy Policy</h1>
    <p class="text-muted" style="margin-bottom:32px;">Last updated: <?= date('d F Y') ?></p>

    <div style="line-height:1.7;">
      <p>This Privacy Policy explains how <?= htmlspecialchars($siteName) ?> ("we", "us", "our") collects, uses, and protects your personal information when you use our website and make purchases from us. We are based in Nigeria and handle personal data in line with the Nigeria Data Protection Act 2023 (NDPA).</p>

      <h3>1. Information we collect</h3>
      <p>When you browse, register an account, or place an order, we may collect: your name, email address, phone number, delivery address, order history, and payment confirmation details (we never see or store your card number — payments are processed directly by Paystack, our payment processor). We also automatically collect basic technical data such as your IP address and browser type via standard web server logs.</p>

      <h3>2. How we use your information</h3>
      <ul>
        <li>To process and deliver your orders, and to communicate with you about them (confirmation, shipping, and delivery updates)</li>
        <li>To maintain your account, order history, and wishlist</li>
        <li>To respond to messages you send us via the contact form</li>
        <li>To send newsletter updates, only if you've opted in, and you can unsubscribe at any time</li>
        <li>To detect and prevent fraud, spam, and abuse of the site</li>
      </ul>

      <h3>3. Sharing your information</h3>
      <p>We do not sell your personal information. We share the minimum necessary data with:</p>
      <ul>
        <li><strong>Paystack</strong>, to process your payment securely</li>
        <li>Delivery/logistics partners, to fulfil and ship your order</li>
        <li>Service providers who help us run the site (e.g. email delivery), bound to keep your data confidential</li>
      </ul>

      <h3>4. Data retention</h3>
      <p>We keep order records for as long as needed for accounting, warranty, and legal purposes. You can request deletion of your account and associated personal data at any time, subject to records we're legally required to keep (e.g. transaction history for tax purposes).</p>

      <h3>5. Your rights</h3>
      <p>You can access, correct, or request deletion of your personal data by contacting us at <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" style="color:var(--accent-dark);"><?= htmlspecialchars($contactEmail) ?></a>. You can also update your delivery details and password directly from <a href="<?= base_url('account/index.php') ?>" style="color:var(--accent-dark);">My Account</a>.</p>

      <h3>6. Cookies</h3>
      <p>We use essential session cookies to keep your cart, wishlist, and login working as you browse — these aren't used for advertising or tracking across other sites.</p>

      <h3>7. Contact us</h3>
      <p>Questions about this policy? Reach us at <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" style="color:var(--accent-dark);"><?= htmlspecialchars($contactEmail) ?></a> or <?= htmlspecialchars($contactAddress) ?>.</p>

      <p class="text-muted" style="margin-top:32px;font-size:0.85rem;">This is a general-purpose template and hasn't been reviewed by a lawyer. Have it checked against your specific business practices and applicable law before relying on it commercially.</p>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
