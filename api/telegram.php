<?php
require_once __DIR__ . '/bootstrap.php';

function telegramRequest(string $method, array $payload): array
{
    $token = envValue('TELEGRAM_BOT_TOKEN');
    if ($token === '' || !function_exists('curl_init')) return ['ok' => false];
    $relay = envValue('TELEGRAM_RELAY_URL');
    $headers = ['Content-Type: application/json'];
    $url = 'https://api.telegram.org/bot' . $token . '/' . $method;
    if ($relay !== '') {
        if (parse_url($relay, PHP_URL_SCHEME) !== 'https' || envValue('TELEGRAM_RELAY_SECRET') === '') return ['ok' => false];
        $url = $relay;
        $headers[] = 'X-Relay-Secret: ' . envValue('TELEGRAM_RELAY_SECRET');
        // The bot token stays on the relay server; it is not sent in requests from the site.
        $payload = ['method' => $method, 'payload' => (object)$payload];
    }
    $handle = curl_init($url);
    curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 5]);
    if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) curl_setopt($handle, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
    $response = curl_exec($handle);
    $transportError = curl_errno($handle);
    $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    $result = is_string($response) ? json_decode($response, true) : null;
    if (!is_array($result) || empty($result['ok'])) {
        // Never log API URLs (they contain the token), request bodies or credentials.
        error_log('Telegram request failed; method=' . $method . '; HTTP=' . $status . '; transport=' . $transportError);
        return ['ok' => false, 'error_code' => (int)($result['error_code'] ?? $status)];
    }
    return $result;
}

function telegramState(callable $callback): mixed
{
    $directory = dirname(__DIR__) . '/.private';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('Bot storage unavailable');
    if (!is_file($directory . '/.htaccess') && file_put_contents($directory . '/.htaccess', "Require all denied\n") === false) throw new RuntimeException('Bot storage protection unavailable');
    $handle = fopen($directory . '/telegram.json', 'c+');
    if (!$handle) throw new RuntimeException('Bot storage unavailable');
    try {
        if (!flock($handle, LOCK_EX)) throw new RuntimeException('Bot storage lock unavailable');
        $raw = stream_get_contents($handle);
        $state = $raw === '' ? ['chats' => [], 'failures' => []] : json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        $result = $callback($state);
        $encoded = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        rewind($handle);
        if (!ftruncate($handle, 0) || fwrite($handle, $encoded) !== strlen($encoded) || !fflush($handle)) throw new RuntimeException('Bot storage write failed');
        return $result;
    } finally { flock($handle, LOCK_UN); fclose($handle); }
}

// Pure state transition: no passwords or usernames from messages are persisted.
function telegramHandleUpdate(array &$state, array $update, int $now): ?array
{
    $message = $update['message'] ?? null;
    if (!is_array($message) || ($message['chat']['type'] ?? '') !== 'private' || !isset($message['text'], $message['chat']['id'])) return null;
    $chatId = (string)$message['chat']['id'];
    $text = trim((string)$message['text']);
    $state['chats'] ??= [];
    $state['failures'] = array_values(array_filter($state['failures'] ?? [], fn($t) => $t > $now - 900));
    // Expire abandoned unauthenticated conversations to keep storage bounded.
    foreach ($state['chats'] as $id => $entry) {
        if (empty($entry['authorized']) && ($entry['seen'] ?? 0) < $now - 86400) unset($state['chats'][$id]);
    }
    $chat = $state['chats'][$chatId] ?? ['authorized' => false, 'step' => 'login', 'failures' => []];
    if ((int)($update['update_id'] ?? -1) <= ($chat['last_update'] ?? -1) && ($chat['seen'] ?? 0) > $now - 86400) return null;
    $chat['last_update'] = (int)$update['update_id'];
    $chat['seen'] = $now;
    $chat['failures'] = array_values(array_filter($chat['failures'] ?? [], fn($t) => $t > $now - 900));
    $reply = ['chat_id' => $chatId, 'text' => '', 'delete_message_id' => null];
    if (($chat['step'] ?? '') === 'password' && !str_starts_with($text, '/')) $reply['delete_message_id'] = $message['message_id'] ?? null;
    if ($text === '/stop' || $text === '/logout') {
        $chat['authorized'] = false; $chat['step'] = 'login'; unset($chat['login_ok']);
        $reply['text'] = 'Уведомления отключены. Для входа отправьте /start.';
    } elseif (!empty($chat['authorized'])) {
        $reply['text'] = 'Вы авторизованы. Новые заявки будут приходить сюда. /stop — отключить уведомления.';
    } elseif (count($chat['failures']) >= 5 || count($state['failures']) >= 30) {
        $reply['text'] = 'Слишком много неудачных попыток. Повторите вход через 15 минут.';
    } elseif ($text === '/start' || $text === '/login') {
        $chat['step'] = 'login'; $chat['expires'] = $now + 300; unset($chat['login_ok']);
        $reply['text'] = 'Для получения заявок войдите в аккаунт. Введите логин:';
    } elseif (($chat['expires'] ?? 0) < $now) {
        $chat['step'] = 'login'; unset($chat['login_ok']);
        $reply['text'] = 'Время входа истекло. Отправьте /start и повторите вход.';
    } elseif ($chat['step'] === 'login') {
        $chat['login_ok'] = hash_equals(envValue('TELEGRAM_ADMIN_LOGIN', 'admin'), $text);
        $chat['step'] = 'password';
        $reply['text'] = 'Введите пароль:';
    } else {
        $hash = envValue('TELEGRAM_ADMIN_PASSWORD_HASH');
        $validPassword = $hash !== '' && password_verify($text, $hash);
        if (!empty($chat['login_ok']) && $validPassword) {
            $chat['authorized'] = true; $chat['failures'] = []; $chat['step'] = 'ready';
            $reply['text'] = 'Вход выполнен. Уведомления о новых заявках включены. /stop — отключить.';
        } else {
            $chat['failures'][] = $now; $state['failures'][] = $now;
            $chat['step'] = 'login';
            $reply['text'] = count($chat['failures']) >= 5 || count($state['failures']) >= 30
                ? 'Слишком много неудачных попыток. Повторите вход через 15 минут.'
                : 'Неверный логин или пароль. Введите логин повторно:';
        }
        unset($chat['login_ok']);
    }
    $state['chats'][$chatId] = $chat;
    return $reply;
}

function notifyTelegramLead(): void
{
    if (envValue('TELEGRAM_BOT_TOKEN') === '') return;
    try {
        $recipients = telegramState(function (&$state) {
            return array_keys(array_filter($state['chats'] ?? [], fn($chat) => !empty($chat['authorized'])));
        });
        // No application data enters the Telegram or relay payload.
        $text = 'На сайте появилась новая заявка.';
        $url = rtrim(envValue('TELEGRAM_SITE_URL', 'https://voronov-art.ru'), '/') . '/admin/';
        foreach ($recipients as $chatId) {
            telegramRequest('sendMessage', ['chat_id' => (string)$chatId, 'text' => $text,
                'link_preview_options' => ['is_disabled' => true],
                'reply_markup' => ['inline_keyboard' => [[['text' => 'Открыть админку', 'url' => $url]]]]]);
        }
    } catch (Throwable $e) { error_log('Telegram notification unavailable'); }
}
