<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

require_once dirname(__DIR__) . '/server/bootstrap.php';
require_once __DIR__ . '/bot.php';

$webhookUrl = 'https://ezhevika-loft.ru/telegram/webhook.php';

$result = telegram_api('setWebhook', [
    'url' => $webhookUrl,
    'secret_token' => telegram_webhook_secret(),
    'allowed_updates' => ['message'],
    'drop_pending_updates' => true,
]);

echo "Telegram webhook configured.\n";
