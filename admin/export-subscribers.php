<?php
require_once __DIR__ . '/includes/auth.php';

$subscribers = $pdo->query('SELECT * FROM newsletter_subscribers ORDER BY created_at DESC')->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="strideco-subscribers-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Email', 'Subscribed At']);
foreach ($subscribers as $s) {
    fputcsv($out, [$s['email'], $s['created_at']]);
}
fclose($out);
exit;
