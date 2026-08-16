<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'About Us';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero" style="padding:0;">
  <div class="container" style="padding:72px 24px;text-align:center;">
    <span class="hero-eyebrow">Our story</span>
    <h1 style="max-width:640px;margin:0 auto 16px;">Built on quality, since day one.</h1>
    <p style="color:rgba(255,255,255,0.7);max-width:520px;margin:0 auto;">Expandable Collection started with a simple idea: great shoes shouldn't mean compromising on comfort, quality, or price.</p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:720px;">
    <p>We're a Nigeria-based footwear store bringing together sneakers, formal shoes, sport trainers, and everyday casual styles — all in one place. Every pair is chosen for comfort and durability first, because shoes that look good but fall apart in a month aren't worth anyone's money.</p>
    <p>What started as a small curated selection has grown into a full catalog spanning four categories, with new styles added regularly. Whether you're dressing for the office, hitting the gym, or just need something reliable for daily wear, we've got a pair for it.</p>
    <p>We ship across Nigeria, offer secure checkout, and stand behind every order with easy exchanges. If something's not right, we want to make it right — <a href="<?= base_url('contact.php') ?>" style="color:var(--accent-dark);font-weight:600;">reach out any time</a>.</p>
  </div>
</section>

<section class="section" style="padding-top:0;">
  <div class="container">
    <div class="cta-banner">
      <div>
        <h2>Ready to find your pair?</h2>
        <p>Browse the full collection and get free shipping over <?= format_price(FREE_SHIPPING_THRESHOLD) ?>.</p>
      </div>
      <a href="<?= base_url('shop.php') ?>" class="btn btn-dark">Shop now</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
