<?php
// Decodes a base64url-encoded path from ?c= and redirects to it.
// Lets us hand out a short "coded" link that still resolves to a real page
// on the site instead of a plain, readable URL.

$code = $_GET['c'] ?? '';
$padded = str_pad($code, strlen($code) + (4 - strlen($code) % 4) % 4, '=');
$path = base64_decode(strtr($padded, '-_', '+/'), true);

$isSafeRelativePath = $path !== false
    && $path !== ''
    && $path[0] === '/'
    && strpos($path, '//') !== 0
    && strpos($path, '://') === false;

if (!$isSafeRelativePath) {
    http_response_code(400);
    exit('Invalid link.');
}

header('Location: ' . $path, true, 302);
exit;
