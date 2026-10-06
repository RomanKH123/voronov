<?php
require_once __DIR__ . '/telegram.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['ok' => false], 405);
$secret = envValue('TELEGRAM_WEBHOOK_SECRET');
if ($secret === '' || !hash_equals($secret, (string)($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? ''))) jsonResponse(['ok' => false], 403);
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 65536) jsonResponse(['ok' => false], 413);
$raw = file_get_contents('php://input', false, null, 0, 65537);
if (strlen($raw) > 65536) jsonResponse(['ok' => false], 413);
$update = json_decode($raw, true);
if (!is_array($update) || !isset($update['update_id'])) jsonResponse(['ok' => false], 400);
try {
    $reply = telegramState(function (&$state) use ($update) { return telegramHandleUpdate($state, $update, time()); });
    if ($reply !== null) {
        if ($reply['delete_message_id'] !== null) telegramRequest('deleteMessage', ['chat_id' => $reply['chat_id'], 'message_id' => $reply['delete_message_id']]);
        unset($reply['delete_message_id']);
        telegramRequest('sendMessage', $reply);
    }
    jsonResponse(['ok' => true]);
} catch (Throwable $e) {
    error_log('Telegram webhook state unavailable');
    jsonResponse(['ok' => false], 503);
}
