<?php
/**
 * Generates a self-contained SVG "product photo" placeholder (or the site
 * favicon) so the storefront never depends on external image files.
 */
require_once __DIR__ . '/includes/functions.php';

function sanitize_hex(string $hex, string $fallback): string {
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) ? $hex : $fallback;
}

function tint(string $hex, float $amountToWhite): string {
    $hex = ltrim($hex, '#');
    [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    $r = (int) round($r + (255 - $r) * $amountToWhite);
    $g = (int) round($g + (255 - $g) * $amountToWhite);
    $b = (int) round($b + (255 - $b) * $amountToWhite);
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function shade(string $hex, float $amountToBlack): string {
    $hex = ltrim($hex, '#');
    [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    $r = (int) round($r * (1 - $amountToBlack));
    $g = (int) round($g * (1 - $amountToBlack));
    $b = (int) round($b * (1 - $amountToBlack));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=604800');

$ink = '#17140F';

// ---- Favicon mode -------------------------------------------------
if (!empty($_GET['icon'])) {
    echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
  <rect width="48" height="48" rx="10" fill="{$ink}"/>
  <g transform="translate(4,10)">
    <path d="M4 22 C4 22 10 10 20 9 C26 8.4 27 12 32 12 C37 12 38 8 43 8 L44 15 C44 15 40 14 37 16 C34 18 33 22 26 22 Z" fill="#FF5A1F"/>
    <path d="M4 22 L44 22 L44 26 C44 26 38 27 30 27 L8 27 C5 27 4 25 4 22 Z" fill="#FF5A1F" opacity="0.55"/>
  </g>
</svg>
SVG;
    exit;
}

// ---- Product placeholder photo ------------------------------------
$name = $_GET['name'] ?? 'Expandable Collection shoe';
$accent = sanitize_hex($_GET['accent'] ?? '#FF6A1A', '#FF6A1A');
$seed = max(0, (int) ($_GET['seed'] ?? 0));

$flip = ($seed % 2 === 0) ? 1 : -1;
$rotate = (($seed % 5) - 2) * 1.4; // -2.8..2.8 deg
$bgLight = tint($accent, 0.88);
$bgLighter = tint($accent, 0.94);
$titleSafe = htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8');
$initial = htmlspecialchars(mb_strtoupper(mb_substr(trim($name), 0, 1)), ENT_XML1 | ENT_QUOTES, 'UTF-8');
if ($initial === '') { $initial = 'S'; }

echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" role="img" aria-labelledby="t">
  <title id="t">{$titleSafe}</title>
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$bgLighter}"/>
      <stop offset="1" stop-color="{$bgLight}"/>
    </linearGradient>
  </defs>
  <rect width="400" height="400" fill="url(#bg)"/>
  <circle cx="200" cy="196" r="138" fill="{$accent}" opacity="0.10"/>
  <g transform="translate(200,196) rotate({$rotate})">
    <circle r="88" fill="none" stroke="{$accent}" stroke-width="1.5" opacity="0.3"/>
    <circle r="76" fill="{$accent}"/>
    <text x="0" y="0" text-anchor="middle" dominant-baseline="central" font-family="'Space Grotesk',sans-serif" font-weight="700" font-size="64" fill="#ffffff">{$initial}</text>
    <g transform="scale({$flip},1)">
      <line x1="86" y1="-52" x2="104" y2="-70" stroke="{$ink}" stroke-width="5" stroke-linecap="round" opacity="0.5"/>
      <line x1="98" y1="-40" x2="116" y2="-58" stroke="{$ink}" stroke-width="5" stroke-linecap="round" opacity="0.32"/>
      <line x1="110" y1="-28" x2="128" y2="-46" stroke="{$ink}" stroke-width="5" stroke-linecap="round" opacity="0.16"/>
    </g>
  </g>
  <text x="24" y="378" font-family="'Space Grotesk',sans-serif" font-size="15" font-weight="700" fill="{$ink}" opacity="0.28">Expandable</text>
</svg>
SVG;
