<?php
// Run on the server after deploying: php api/telegram-setup.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/telegram.php';
foreach (['TELEGRAM_BOT_TOKEN', 'TELEGRAM_WEBHOOK_SECRET', 'TELEGRAM_ADMIN_PASSWORD_HASH'] as $key) {
    if (envValue($key) === '') { fwrite(STDERR, "Missing setting: $key\n"); exit(1); }
}
$url = rtrim(envValue('TELEGRAM_SITE_URL'), '/');
if (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') { fwrite(STDERR, "TELEGRAM_SITE_URL must be an HTTPS URL\n"); exit(1); }
telegramState(function (&$state) { return null; });
$webhookUrl = envValue('TELEGRAM_WEBHOOK_URL', $url . '/api/telegram-webhook.php');
if (!filter_var($webhookUrl, FILTER_VALIDATE_URL) || parse_url($webhookUrl, PHP_URL_SCHEME) !== 'https') { fwrite(STDERR, "TELEGRAM_WEBHOOK_URL must be an HTTPS URL\n"); exit(1); }
$result = telegramRequest('setWebhook', ['url' => $webhookUrl,
    'secret_token' => envValue('TELEGRAM_WEBHOOK_SECRET'), 'allowed_updates' => ['message'], 'max_connections' => 1]);
if (empty($result['ok'])) { fwrite(STDERR, "Webhook registration failed. Check server logs and settings.\n"); exit(1); }
echo "Webhook registered. Open the bot and send /start to sign in.\n";
