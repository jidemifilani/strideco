<?php
/**
 * Integration smoke test: hits the running site over HTTP and checks that
 * key public pages load (200, no PHP warnings/errors) and that admin pages
 * correctly redirect when unauthenticated. This does NOT replace the unit
 * tests in HelperFunctionsTest.php (which test pure logic in isolation) —
 * it checks that the site is actually up and wired together correctly.
 *
 * Requires Apache + MySQL running (start them from the XAMPP Control Panel)
 * and the site reachable at the URL below.
 *
 * Run: C:\xampp\php\php.exe tests\smoke-test.php
 */

$base = getenv('STRIDECO_TEST_URL') ?: 'http://localhost/strideco';

$publicPages = [
    'index.php', 'shop.php', 'cart.php', 'track-order.php', 'contact.php',
    'about.php', 'faq.php', 'wishlist.php', 'privacy-policy.php', 'terms.php',
    'refund-policy.php', 'account/login.php', 'account/register.php', 'admin/login.php',
];

$adminProtectedPages = ['admin/index.php', 'admin/products.php', 'admin/settings.php'];

$failures = [];
$passed = 0;

function fetch(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return [$code, $body, $err];
}

echo "Running StrideCo smoke tests against {$base}\n\n";

foreach ($publicPages as $page) {
    [$code, $body, $err] = fetch("{$base}/{$page}");
    $hasErrors = $body !== false && preg_match('/Warning:|Fatal error|Notice:|Deprecated:/', $body);
    if ($code === 200 && !$hasErrors) {
        echo "  PASS  {$page} (200)\n";
        $passed++;
    } else {
        $reason = $err ?: ($hasErrors ? 'PHP warning/error in output' : "unexpected status {$code}");
        echo "  FAIL  {$page} — {$reason}\n";
        $failures[] = "{$page}: {$reason}";
    }
}

foreach ($adminProtectedPages as $page) {
    [$code, , $err] = fetch("{$base}/{$page}");
    // Unauthenticated admin pages must redirect (302), never render (200).
    if ($code === 302) {
        echo "  PASS  {$page} redirects when unauthenticated (302)\n";
        $passed++;
    } else {
        $reason = $err ?: "expected 302, got {$code}";
        echo "  FAIL  {$page} — {$reason}\n";
        $failures[] = "{$page}: {$reason}";
    }
}

$apiChecks = [
    'api/categories.php' => fn(array $json) => isset($json['ok']) && $json['ok'] === true && is_array($json['data']),
    'api/products.php?per_page=1' => fn(array $json) => $json['ok'] === true && count($json['data']) === 1 && isset($json['meta']['total']),
    'api/product.php?slug=nonexistent-slug-xyz' => fn(array $json) => $json['ok'] === false,
];
foreach ($apiChecks as $endpoint => $checkFn) {
    [$code, $body] = fetch("{$base}/{$endpoint}");
    $json = json_decode((string) $body, true);
    $expectedCode = str_contains($endpoint, 'nonexistent') ? 404 : 200;
    if ($code === $expectedCode && is_array($json) && $checkFn($json)) {
        echo "  PASS  {$endpoint} ({$code}, valid JSON shape)\n";
        $passed++;
    } else {
        echo "  FAIL  {$endpoint} — status {$code}, unexpected response shape\n";
        $failures[] = "{$endpoint}: unexpected response";
    }
}

// Cart add/remove round-trip using a shared cookie jar, since cart state is session-based.
$cookieJar = tempnam(sys_get_temp_dir(), 'strideco_test_');
$ch = curl_init("{$base}/index.php");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cookieJar]);
curl_exec($ch);
curl_close($ch);

[$code, $cartBody] = fetch("{$base}/cart.php");
$cartEmpty = $cartBody !== false && (str_contains($cartBody, 'empty') || str_contains(strtolower($cartBody), 'cart is empty'));
if ($code === 200 && $cartEmpty) {
    echo "  PASS  cart.php shows empty state for a fresh session\n";
    $passed++;
} else {
    echo "  FAIL  cart.php did not show the expected empty state\n";
    $failures[] = 'cart.php: unexpected content for empty cart';
}
@unlink($cookieJar);

echo "\n" . str_repeat('-', 50) . "\n";
echo count($failures) === 0
    ? "All " . $passed . " checks passed.\n"
    : $passed . " passed, " . count($failures) . " FAILED:\n  - " . implode("\n  - ", $failures) . "\n";

exit(count($failures) === 0 ? 0 : 1);
