<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/bootstrap.php';

$configuredDays = filter_var(getenv('LEAD_RETENTION_DAYS') ?: '1095', FILTER_VALIDATE_INT);
$days = is_int($configuredDays) ? max(30, min($configuredDays, 3650)) : 1095;
$pdo = db();
$deletedLeads = $pdo->exec("DELETE FROM leads WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL {$days} DAY)");
$deletedLimits = $pdo->exec('DELETE FROM rate_limits WHERE created_at < UNIX_TIMESTAMP() - 86400');

echo "Deleted leads: {$deletedLeads}; rate-limit records: {$deletedLimits}\n";
