<?php
/**
 * Automation: emails customers who typed their email at checkout but never
 * finished placing the order, with a link that restores their exact cart.
 *
 * Run this on a real schedule in production for the most reliable timing:
 *   Linux/cron:   0 * * * *  php /path/to/strideco/cron/send-abandoned-cart-emails.php
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

$count = send_abandoned_cart_reminders($pdo);
echo date('Y-m-d H:i:s') . " — sent {$count} abandoned-cart reminder(s).\n";
