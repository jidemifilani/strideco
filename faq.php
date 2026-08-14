<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'FAQ';
require_once __DIR__ . '/includes/header.php';

$faqs = [
    ['q' => 'How long does delivery take?', 'a' => 'Orders within Lagos typically arrive in 1-3 business days. Other states across Nigeria usually take 3-7 business days depending on location.'],
    ['q' => 'What sizes do you carry?', 'a' => 'Most styles are available in UK sizes 38 through 45. Exact size availability is shown on each product page — sizes that are out of stock are marked and can\'t be selected.'],
    ['id' => 'faq-sizing', 'q' => 'How do I know what size to order?', 'a' => 'Our sizing follows standard UK shoe sizes. If you\'re between sizes, we generally recommend sizing up for sneakers and true-to-size for formal shoes.'],
    ['q' => 'What payment methods do you accept?', 'a' => 'We accept secure online payment via Paystack, which supports major debit/credit cards, bank transfer, and USSD.'],
    ['q' => 'Can I return or exchange a pair?', 'a' => 'Yes — unworn shoes in original packaging can be exchanged within 7 days of delivery. Contact us with your order reference to start an exchange.'],
    ['q' => 'How do I track my order?', 'a' => 'Use the <a href="' . base_url('track-order.php') . '" style="color:var(--accent-dark);font-weight:600;">Track Your Order</a> page with your order reference and the email you checked out with.'],
    ['q' => 'Do you offer free shipping?', 'a' => 'Yes — orders over ' . format_price(FREE_SHIPPING_THRESHOLD) . ' qualify for free shipping automatically at checkout.'],
    ['q' => 'I have a coupon code — where do I enter it?', 'a' => 'You can apply a coupon code on the checkout page, right in the order summary panel.'],
];
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container" style="max-width:720px;">
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / FAQ</div>
    <h1 style="margin-bottom:8px;">Frequently asked questions</h1>
    <p class="text-muted" style="margin-bottom:32px;">Can't find what you're looking for? <a href="<?= base_url('contact.php') ?>" style="color:var(--accent-dark);font-weight:600;">Contact us</a>.</p>

    <div style="display:flex;flex-direction:column;gap:12px;">
      <?php foreach ($faqs as $item): ?>
        <details class="faq-item" <?= isset($item['id']) ? 'id="' . htmlspecialchars($item['id']) . '"' : '' ?>>
          <summary><?= htmlspecialchars($item['q']) ?></summary>
          <p><?= $item['a'] ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
