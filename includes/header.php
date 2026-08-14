<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
start_session_if_needed();

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$currentPath = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$activeCategorySlug = $_GET['category'] ?? '';
$cartCount = cart_count();
$wishlistCount = wishlist_count($pdo);
$siteName = get_setting($pdo, 'site_name', SITE_NAME);
$accentColor = get_setting($pdo, 'accent_color', '#FF5A1F');
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $accentColor)) { $accentColor = '#FF5A1F'; }
$htmlTitle = isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' · ' . $siteName : $siteName . ' — Shoes for every stride';

// Site maintenance mode (Admin → Theme & Content): shows a closed splash on
// every storefront page instead of the normal site. Only pages that include
// this file are affected — the admin panel has its own header and stays
// fully reachable, and the Paystack webhook/callback (no header) keep
// confirming in-flight payments normally even while this is on.
if (get_setting($pdo, 'maintenance_mode', '0') === '1') {
    $maintenanceMessage = get_setting($pdo, 'maintenance_message', "We're temporarily closed while we catch up on a large order — thanks for your patience!");
    $maintenanceReopenAt = get_setting($pdo, 'maintenance_reopen_at', '');
    $reopenLabel = '';
    if ($maintenanceReopenAt !== '') {
        $reopenTimestamp = strtotime($maintenanceReopenAt);
        if ($reopenTimestamp !== false) {
            $reopenLabel = date('l, d F Y \a\t H:i', $reopenTimestamp);
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?> — We'll be right back</title>
    <link rel="icon" href="<?= base_url('image.php?icon=1') ?>" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <style>
      :root {
        --accent: <?= $accentColor ?>;
        --accent-dark: <?= hex_shade($accentColor, 0.12) ?>;
        --accent-tint: <?= hex_tint($accentColor, 0.93) ?>;
      }
      body { min-height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center; padding: 24px; }
      .maintenance-box { max-width: 480px; }
      .maintenance-logo { display: inline-flex; align-items: center; gap: 8px; font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 1.3rem; margin-bottom: 28px; color: var(--ink); }
      .maintenance-logo svg { color: var(--accent); }
      .maintenance-box h1 { font-size: 1.7rem; margin-bottom: 14px; }
      .maintenance-box p { color: var(--ink-soft); line-height: 1.6; margin-bottom: 8px; }
      .maintenance-reopen { display: inline-block; margin-top: 18px; padding: 10px 22px; border-radius: 999px; background: var(--accent-tint); color: var(--accent-dark); font-weight: 600; font-size: 0.92rem; }
    </style>
    </head>
    <body>
      <div class="maintenance-box">
        <div class="maintenance-logo">
          <svg width="30" height="20" viewBox="0 0 48 32" aria-hidden="true" fill="currentColor">
            <path d="M4 22 C4 22 10 10 20 9 C26 8.4 27 12 32 12 C37 12 38 8 43 8 L44 15 C44 15 40 14 37 16 C34 18 33 22 26 22 Z"/>
            <path d="M4 22 L44 22 L44 26 C44 26 38 27 30 27 L8 27 C5 27 4 25 4 22 Z" opacity="0.55"/>
          </svg>
          <?= htmlspecialchars($siteName) ?>
        </div>
        <h1>We'll be right back</h1>
        <p><?= nl2br(htmlspecialchars($maintenanceMessage)) ?></p>
        <?php if ($reopenLabel !== ''): ?>
          <div class="maintenance-reopen">Reopening <?= htmlspecialchars($reopenLabel) ?></div>
        <?php endif; ?>
      </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($htmlTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($siteName) ?> — sneakers, formal, sport and casual shoes for every stride.">
<link rel="icon" href="<?= base_url('image.php?icon=1') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
<style>:root {
  --accent: <?= $accentColor ?>;
  --accent-dark: <?= hex_shade($accentColor, 0.12) ?>;
  --accent-tint: <?= hex_tint($accentColor, 0.93) ?>;
}</style>
</head>
<body>

<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
  <div class="container header-inner">
    <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <a href="<?= base_url('index.php') ?>" class="logo">
      <svg class="logo-mark" viewBox="0 0 48 32" aria-hidden="true">
        <path d="M4 22 C4 22 10 10 20 9 C26 8.4 27 12 32 12 C37 12 38 8 43 8 L44 15 C44 15 40 14 37 16 C34 18 33 22 26 22 Z" fill="currentColor"/>
        <path d="M4 22 L44 22 L44 26 C44 26 38 27 30 27 L8 27 C5 27 4 25 4 22 Z" fill="currentColor" opacity="0.55"/>
      </svg>
      <span class="logo-text"><?= htmlspecialchars($siteName) ?></span>
    </a>

    <nav class="main-nav" id="mainNav">
      <a href="<?= base_url('index.php') ?>" class="<?= $currentPath === 'index.php' ? 'active' : '' ?>">Home</a>
      <div class="nav-dropdown">
        <a href="<?= base_url('shop.php') ?>" class="<?= $currentPath === 'shop.php' && $activeCategorySlug === '' ? 'active' : '' ?>">
          Shop <svg width="10" height="6" viewBox="0 0 10 6" aria-hidden="true"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
        </a>
        <div class="dropdown-panel">
          <?php foreach ($categories as $cat): ?>
            <a href="<?= base_url('shop.php?category=' . urlencode($cat['slug'])) ?>"><?= htmlspecialchars($cat['name']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php foreach ($categories as $cat): ?>
        <a href="<?= base_url('shop.php?category=' . urlencode($cat['slug'])) ?>" class="nav-cat-link <?= $activeCategorySlug === $cat['slug'] ? 'active' : '' ?>"><?= htmlspecialchars($cat['name']) ?></a>
      <?php endforeach; ?>
    </nav>

    <form class="search-form" action="<?= base_url('shop.php') ?>" method="get" role="search">
      <input type="text" name="search" placeholder="Search shoes…" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" aria-label="Search shoes">
      <button type="submit" aria-label="Search">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><circle cx="7" cy="7" r="5.5" stroke="currentColor" stroke-width="1.5"/><path d="M15 15l-3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      </button>
    </form>

    <div class="header-icons">
      <div class="nav-dropdown account-dropdown">
        <a href="<?= is_customer_logged_in() ? base_url('account/index.php') : base_url('account/login.php') ?>" class="icon-link" aria-label="Account">
          <svg width="21" height="21" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.6"/><path d="M4.5 20c1.4-3.6 4.4-5.5 7.5-5.5s6.1 1.9 7.5 5.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        </a>
        <?php if (is_customer_logged_in()): ?>
        <div class="dropdown-panel">
          <span style="padding:9px 12px;font-size:0.8rem;color:var(--muted);">Hi, <?= htmlspecialchars(explode(' ', $_SESSION['customer_name'] ?? 'there')[0]) ?></span>
          <a href="<?= base_url('account/index.php') ?>">My Account</a>
          <a href="<?= base_url('account/orders.php') ?>">My Orders</a>
          <a href="<?= base_url('wishlist.php') ?>">Wishlist</a>
          <a href="<?= base_url('account/logout.php') ?>">Log out</a>
        </div>
        <?php endif; ?>
      </div>

      <a href="<?= base_url('wishlist.php') ?>" class="icon-link" aria-label="View wishlist">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.8-9C.7 8.1 2 4.5 5.4 3.6c2-.5 4 .3 5.1 2 .3.4.9.4 1.2 0 1.1-1.7 3.1-2.5 5.1-2 3.4.9 4.7 4.5 3.2 7.9-2.3 4.4-9.8 9-9.8 9Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        <?php if ($wishlistCount > 0): ?><span class="cart-badge"><?= $wishlistCount ?></span><?php endif; ?>
      </a>

      <a href="<?= base_url('cart.php') ?>" class="cart-link" aria-label="View cart">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2l2.4 12.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L21 8H6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9.5" cy="21" r="1.4" fill="currentColor"/><circle cx="17.5" cy="21" r="1.4" fill="currentColor"/></svg>
        <?php if ($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
      </a>
    </div>
  </div>
</header>

<main id="main">
<?php render_flash(); ?>
