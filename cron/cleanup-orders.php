<?php
/**
 * Automation: cancels orders stuck in "pending" payment for 24+ hours
 * (customer abandoned checkout at Paystack, or never completed it).
 *
 * Run this on a real schedule in production for the most reliable timing:
 *   Linux/cron:   0 * * * *  php /path/to/strideco/cron/cleanup-orders.php
 *   Windows Task Scheduler: run C:\xampp\php\php.exe with this file as the
 *   argument, hourly.
 *
 * The site also runs this opportunistically (at most once an hour) whenever
 * the admin dashboard loads, so it works without a real cron job configured
 * too — this script is for production reliability, not a hard requirement.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script is for command-line/scheduled use only.');
}

$count = cleanup_abandoned_orders($pdo);
echo date('Y-m-d H:i:s') . " — cancelled {$count} abandoned order(s).\n";
