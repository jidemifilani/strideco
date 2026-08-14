<?php
/**
 * Automation: dumps the full "strideco" database to backups/ via mysqldump,
 * then prunes dumps older than BACKUP_RETENTION_DAYS so disk usage doesn't
 * grow unbounded.
 *
 * Run this on a real schedule in production:
 *   Linux/cron:   0 3 * * *  php /path/to/strideco/cron/backup-database.php
 *   Windows Task Scheduler: run C:\xampp\php\php.exe with this file as the
 *   argument, daily (e.g. 3am).
 *
 * Unlike cleanup-orders.php, this has no "opportunistic" fallback — a backup
 * only matters if it runs reliably, so it needs a real schedule configured
 * (see README "Backups" section for the exact Task Scheduler steps).
 *
 * The backups/ folder is blocked from web access via backups/.htaccess —
 * database dumps must never be reachable over HTTP.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script is for command-line/scheduled use only.');
}

if (!is_file(MYSQLDUMP_BIN)) {
    fwrite(STDERR, "mysqldump not found at " . MYSQLDUMP_BIN . " — check MYSQLDUMP_BIN in config/config.php.\n");
    exit(1);
}

$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$filename = 'strideco_' . date('Y-m-d_His') . '.sql';
$filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;

$cmd = escapeshellarg(MYSQLDUMP_BIN)
    . ' --host=' . escapeshellarg(DB_HOST)
    . ' --user=' . escapeshellarg(DB_USER)
    . ' --single-transaction --routines --triggers '
    . escapeshellarg(DB_NAME)
    . ' --result-file=' . escapeshellarg($filepath);

// Passed via env var (not a CLI arg) so the password never appears in the
// process list; omitted entirely for the standard passwordless local XAMPP user.
if (DB_PASS !== '') {
    putenv('MYSQL_PWD=' . DB_PASS);
}

exec($cmd . ' 2>&1', $output, $exitCode);

if (DB_PASS !== '') {
    putenv('MYSQL_PWD');
}

if ($exitCode !== 0 || !is_file($filepath) || filesize($filepath) === 0) {
    fwrite(STDERR, "Backup FAILED: " . implode("\n", $output) . "\n");
    if (is_file($filepath)) {
        unlink($filepath);
    }
    exit(1);
}

$sizeKb = round(filesize($filepath) / 1024, 1);
echo date('Y-m-d H:i:s') . " — backup created: {$filename} ({$sizeKb} KB)\n";

$cutoff = time() - (BACKUP_RETENTION_DAYS * 86400);
$pruned = 0;
foreach (glob($backupDir . '/strideco_*.sql') as $oldFile) {
    if (filemtime($oldFile) < $cutoff) {
        unlink($oldFile);
        $pruned++;
    }
}
if ($pruned > 0) {
    echo date('Y-m-d H:i:s') . " — pruned {$pruned} backup(s) older than " . BACKUP_RETENTION_DAYS . " days.\n";
}
