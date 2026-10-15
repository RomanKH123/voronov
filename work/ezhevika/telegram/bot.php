<?php
declare(strict_types=1);

function telegram_api(string $method, array $payload): array
{
    $token = env_required('TELEGRAM_BOT_TOKEN');
    if (!preg_match('/^[0-9]+:[A-Za-z0-9_-]{20,}$/', $token)) {
        throw new RuntimeException('TELEGRAM_BOT_TOKEN has an invalid format.');
    }

    $url = 'https://api.telegram.org/bot' . $token . '/' . $method;
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
        ]);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if (!is_string($response) || $status < 200 || $status >= 300) {
            throw new RuntimeException('Telegram request failed: ' . ($error ?: "HTTP {$status}"));
        }
    } else {
        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nConnection: close\r\n",
            'content' => $body,
            'timeout' => 5,
            'ignore_errors' => true,
        ]]);
        $response = @file_get_contents($url, false, $context);
        if (!is_string($response)) {
            throw new RuntimeException('Telegram request failed. Install PHP cURL or enable allow_url_fopen.');
        }
    }

    $result = json_decode($response, true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($result) || ($result['ok'] ?? false) !== true) {
        throw new RuntimeException('Telegram API rejected the request.');
    }
    return $result;
}

function telegram_webhook_secret(): string
{
    return hash_hmac('sha256', 'ezhevika-telegram-webhook', env_required('TELEGRAM_BOT_TOKEN'));
}

function telegram_send_message(int|string $chatId, string $text): void
{
    telegram_api('sendMessage', [
        'chat_id' => $chatId,
        'text' => $text,
    ]);
}

function telegram_state_path(): string
{
    return __DIR__ . '/storage/state.json';
}

function telegram_state(callable $callback): mixed
{
    $path = telegram_state_path();
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create Telegram state directory.');
    }

    $handle = fopen($path, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        throw new RuntimeException('Unable to lock Telegram state.');
    }

    try {
        $contents = stream_get_contents($handle);
        $state = $contents ? json_decode($contents, true) : [];
        if (!is_array($state)) {
            $state = [];
        }
        $state['chats'] = is_array($state['chats'] ?? null) ? $state['chats'] : [];
        $result = $callback($state);
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        fflush($handle);
        return $result;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
        @chmod($path, 0600);
    }
}

function telegram_authorized_chat_ids(): array
{
    return telegram_state(static function (array &$state): array {
        $ids = [];
        foreach ($state['chats'] as $chatId => $chat) {
            if (($chat['authorized'] ?? false) === true) {
                $ids[] = (string) $chatId;
            }
        }
        return $ids;
    });
}

function telegram_notify_new_lead(): void
{
    if (trim((string) getenv('TELEGRAM_BOT_TOKEN')) === '') {
        return;
    }

    foreach (telegram_authorized_chat_ids() as $chatId) {
        try {
            telegram_send_message($chatId, '🔔 Поступила новая заявка с сайта.');
        } catch (Throwable $error) {
            error_log("Telegram notification failed for chat {$chatId}: " . $error->getMessage());
        }
    }
}
