<?php
// Exercise the actual notification function with a fake transport: no real messages.
$sent = [];
function envValue($key, $default = '') { return ['TELEGRAM_BOT_TOKEN' => 'test', 'TELEGRAM_SITE_URL' => 'https://site.example'][$key] ?? $default; }
function telegramState($callback) {
    $state = ['chats' => ['123' => ['authorized' => true], '456' => ['authorized' => false]]];
    return $callback($state);
}
function telegramRequest($method, $payload) { global $sent; $sent[] = [$method, $payload]; return ['ok' => true]; }
$source = file_get_contents(__DIR__ . '/../api/telegram.php');
eval(substr($source, strpos($source, 'function notifyTelegramLead(')));
notifyTelegramLead();
$expected = [['sendMessage', [
    'chat_id' => '123', 'text' => 'На сайте появилась новая заявка.',
    'link_preview_options' => ['is_disabled' => true],
    'reply_markup' => ['inline_keyboard' => [[['text' => 'Открыть админку', 'url' => 'https://site.example/admin/']]]]
]]];
if ($sent !== $expected || (new ReflectionFunction('notifyTelegramLead'))->getNumberOfParameters() !== 0) {
    fwrite(STDERR, "FAIL: notification privacy\n"); exit(1);
}
echo "PASS: notification contains only generic text and admin link, no application data, authorized recipients only\n";
