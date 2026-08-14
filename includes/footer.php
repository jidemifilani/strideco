</main>

<?php
$footerSiteName = $siteName ?? get_setting($pdo, 'site_name', SITE_NAME);
$socialInstagram = get_setting($pdo, 'social_instagram');
$socialTwitter = get_setting($pdo, 'social_twitter');
$socialTiktok = get_setting($pdo, 'social_tiktok');
$footerEmail = get_setting($pdo, 'contact_email', 'hello@strideco.example');
$footerPhone = get_setting($pdo, 'contact_phone', '+234 800 000 0000');
$footerAddress = get_setting($pdo, 'contact_address', 'Lagos, Nigeria');
?>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <a href="<?= base_url('index.php') ?>" class="logo">
        <svg class="logo-mark" viewBox="0 0 48 32" aria-hidden="true">
          <path d="M4 22 C4 22 10 10 20 9 C26 8.4 27 12 32 12 C37 12 38 8 43 8 L44 15 C44 15 40 14 37 16 C34 18 33 22 26 22 Z" fill="currentColor"/>
          <path d="M4 22 L44 22 L44 26 C44 26 38 27 30 27 L8 27 C5 27 4 25 4 22 Z" fill="currentColor" opacity="0.55"/>
        </svg>
        <span class="logo-text"><?= htmlspecialchars($footerSiteName) ?></span>
      </a>
      <p>Sneakers, formal, sport and casual shoes built for every stride. Quality footwear, honest prices.</p>
      <form action="<?= base_url('newsletter-subscribe.php') ?>" method="post" style="display:flex;gap:8px;max-width:320px;margin-bottom:18px;">
        <?= csrf_field() ?>
        <?= honeypot_field() ?>
        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
        <input type="email" name="email" required placeholder="Your email" style="flex:1;padding:10px 14px;border-radius:999px;border:1px solid rgba(255,255,255,0.2);background:rgba(255,255,255,0.06);color:#fff;">
        <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">Subscribe</button>
      </form>
      <div class="social-links">
        <a href="<?= $socialInstagram ? htmlspecialchars($socialInstagram) : '#' ?>" aria-label="Instagram"<?= $socialInstagram ? ' target="_blank" rel="noopener"' : '' ?>>IG</a>
        <a href="<?= $socialTwitter ? htmlspecialchars($socialTwitter) : '#' ?>" aria-label="X / Twitter"<?= $socialTwitter ? ' target="_blank" rel="noopener"' : '' ?>>X</a>
        <a href="<?= $socialTiktok ? htmlspecialchars($socialTiktok) : '#' ?>" aria-label="TikTok"<?= $socialTiktok ? ' target="_blank" rel="noopener"' : '' ?>>TT</a>
      </div>
    </div>

    <div class="footer-col">
      <h4>Shop</h4>
      <ul>
        <?php
        $footerCategories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
        foreach ($footerCategories as $cat): ?>
          <li><a href="<?= base_url('shop.php?category=' . urlencode($cat['slug'])) ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Help</h4>
      <ul>
        <li><a href="<?= base_url('track-order.php') ?>">Track your order</a></li>
        <li><a href="<?= base_url('faq.php') ?>">FAQ</a></li>
        <li><a href="<?= base_url('contact.php') ?>">Contact us</a></li>
        <li><a href="<?= base_url('about.php') ?>">About StrideCo</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Contact</h4>
      <ul>
        <li><?= htmlspecialchars($footerEmail) ?></li>
        <li><?= htmlspecialchars($footerPhone) ?></li>
        <li><?= htmlspecialchars($footerAddress) ?></li>
      </ul>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="container footer-bottom-inner">
      <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($footerSiteName) ?>. All rights reserved.</span>
      <span class="footer-legal-links">
        <a href="<?= base_url('privacy-policy.php') ?>">Privacy Policy</a> &middot;
        <a href="<?= base_url('terms.php') ?>">Terms of Service</a> &middot;
        <a href="<?= base_url('refund-policy.php') ?>">Refund &amp; Returns</a>
      </span>
      <span class="footer-note">Payments secured by Paystack</span>
    </div>
  </div>
</footer>

<div class="modal-overlay" id="quickViewOverlay">
  <div class="modal-box" id="quickViewBox" role="dialog" aria-modal="true" aria-label="Quick view">
    <button type="button" class="modal-close" id="quickViewClose" aria-label="Close">&times;</button>
    <div id="quickViewContent" class="modal-content-inner">
      <div class="modal-loading">Loading&hellip;</div>
    </div>
  </div>
</div>

<script>window.STRIDECO_BASE = <?= json_encode(base_url()) ?>;</script>
<script src="<?= base_url('assets/js/main.js') ?>"></script>
</body>
</html>
