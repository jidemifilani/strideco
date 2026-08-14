<?php
$adminCurrentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> · StrideCo Admin</title>
<link rel="icon" href="<?= base_url('image.php?icon=1') ?>" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <a href="<?= base_url('admin/index.php') ?>" class="logo">
      <svg class="logo-mark" viewBox="0 0 48 32" aria-hidden="true">
        <path d="M4 22 C4 22 10 10 20 9 C26 8.4 27 12 32 12 C37 12 38 8 43 8 L44 15 C44 15 40 14 37 16 C34 18 33 22 26 22 Z" fill="currentColor"/>
        <path d="M4 22 L44 22 L44 26 C44 26 38 27 30 27 L8 27 C5 27 4 25 4 22 Z" fill="currentColor" opacity="0.55"/>
      </svg>
      <span class="logo-text">StrideCo</span>
    </a>
    <nav class="admin-nav">
      <a href="<?= base_url('admin/index.php') ?>" class="<?= $adminCurrentPage === 'index.php' ? 'active' : '' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6V11h-6v9Zm0-16v5h6V4h-6Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        Dashboard
      </a>
      <a href="<?= base_url('admin/products.php') ?>" class="<?= in_array($adminCurrentPage, ['products.php','product-form.php']) ? 'active' : '' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M3 7h18M3 12h18M3 17h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Products
      </a>
      <a href="<?= base_url('admin/orders.php') ?>" class="<?= in_array($adminCurrentPage, ['orders.php','order-view.php']) ? 'active' : '' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M6 3h12l1 5H5l1-5Zm-1 5h14l-1 13H6L5 8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        Orders
      </a>
      <a href="<?= base_url('admin/categories.php') ?>" class="<?= in_array($adminCurrentPage, ['categories.php','category-form.php']) ? 'active' : '' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="8" height="8" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="13" y="3" width="8" height="8" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="3" y="13" width="8" height="8" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="13" y="13" width="8" height="8" rx="1.5" stroke="currentColor" stroke-width="1.6"/></svg>
        Categories
      </a>
      <a href="<?= base_url('admin/reviews.php') ?>" class="<?= $adminCurrentPage === 'reviews.php' ? 'active' : '' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M12 2.5l2.6 5.6 6 .7-4.5 4.1 1.2 6-5.3-3-5.3 3 1.2-6-4.5-4.1 6-.7L12 2.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        Reviews
      </a>
      <a href="<?= base_url('admin/coupons.php') ?>" class="<?= in_array($adminCurrentPage, ['coupons.php','coupon-form.php']) ? 'active' : '' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4V8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        Coupons
      </a>
      <a href="<?= base_url('admin/shipping-zones.php') ?>" class="<?= in_array($adminCurrentPage, ['shipping-zones.php','shipping-zone-form.php']) ? 'active' : '' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M3 7h11v8H3V7Zm11 3h4l3 3v2h-7v-5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="7" cy="18" r="1.6" stroke="currentColor" stroke-width="1.4"/><circle cx="17" cy="18" r="1.6" stroke="currentColor" stroke-width="1.4"/></svg>
        Shipping &amp; Tax
      </a>
      <a href="<?= base_url('admin/gift-cards.php') ?>" class="<?= $adminCurrentPage === 'gift-cards.php' ? 'active' : '' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><rect x="3" y="8" width="18" height="12" rx="1.5" stroke="currentColor" stroke-width="1.6"/><path d="M3 12h18M12 8v12" stroke="currentColor" stroke-width="1.6"/><path d="M12 8c0-2 1.8-4 4-4s2.5 2.5.5 4M12 8c0-2-1.8-4-4-4s-2.5 2.5-.5 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Gift Cards
      </a>
      <a href="<?= base_url('admin/abandoned-carts.php') ?>" class="<?= $adminCurrentPage === 'abandoned-carts.php' ? 'active' : '' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2.4 12.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L21 8H6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM17 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" fill="currentColor"/><path d="M2 2l20 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Abandoned Carts
      </a>
      <a href="<?= base_url('admin/messages.php') ?>" class="<?= in_array($adminCurrentPage, ['messages.php','subscribers.php']) ? 'active' : '' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4V6Zm0 0 8 7 8-7" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        Messages
      </a>
      <?php if (is_super_admin()): ?>
      <a href="<?= base_url('admin/settings.php') ?>" class="<?= $adminCurrentPage === 'settings.php' ? 'active' : '' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/><path d="M19.4 13a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V19a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1.08-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H4a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 5.6 8.6a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H10a1.65 1.65 0 0 0 1-1.51V2a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V8a1.65 1.65 0 0 0 1.51 1H20a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
        Theme &amp; Content
      </a>
      <a href="<?= base_url('admin/admins.php') ?>" class="<?= in_array($adminCurrentPage, ['admins.php','admin-form.php']) ? 'active' : '' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><circle cx="9" cy="8" r="3" stroke="currentColor" stroke-width="1.6"/><path d="M2.5 20c1-3.5 3.5-5.5 6.5-5.5s5.5 2 6.5 5.5M16 4.5c1.7.3 3 1.8 3 3.5s-1.3 3.2-3 3.5M19 14c2 .5 3.3 1.9 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Admin Users
      </a>
      <?php endif; ?>
    </nav>
    <div class="admin-sidebar-footer">
      <a href="<?= base_url('index.php') ?>" target="_blank" rel="noopener">&larr; View store</a>
      <a href="<?= base_url('admin/logout.php') ?>">Log out</a>
    </div>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <h1><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
      <span class="admin-user">Hi, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'admin') ?></span>
    </div>
    <div class="admin-content">
      <?php render_flash(); ?>
