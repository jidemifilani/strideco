<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/plain; charset=utf-8');

$lines = [
    'User-agent: *',
    'Disallow: ' . base_url('admin/'),
    'Disallow: ' . base_url('account/'),
    'Disallow: ' . base_url('cart.php'),
    'Disallow: ' . base_url('checkout.php'),
    'Disallow: ' . base_url('place-order.php'),
    'Disallow: ' . base_url('paystack/'),
    '',
    'Sitemap: ' . full_base_url('sitemap.xml'),
];
echo implode("\n", $lines) . "\n";
