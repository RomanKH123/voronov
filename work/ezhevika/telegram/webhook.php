<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/server/bootstrap.php';
require_once __DIR__ . '/bot.php';
require_https();
security_headers();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('{"ok":false}');
}

try {
    $expectedSecret = telegram_webhook_secret();
    $receivedSecret = (string) ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '');
    if ($receivedSecret === '' || !hash_equals($expectedSecret, $receivedSecret)) {
        http_response_code(403);
        exit('{"ok":false}');
    }

    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength < 2 || $contentLength > 65536) {
        http_response_code(413);
        exit('{"ok":false}');
    }

    $update = json_decode((string) file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
    $message = $update['message'] ?? null;
    if (!is_array($message) || !isset($message['chat']['id'], $message['text'])) {
        exit('{"ok":true}');
    }

    $chatId = (string) $message['chat']['id'];
    $messageId = (int) ($message['message_id'] ?? 0);
    $text = trim((string) $message['text']);

    if (($message['chat']['type'] ?? '') !== 'private') {
        telegram_send_message($chatId, 'Авторизация доступна только в личном чате с ботом.');
        exit('{"ok":true}');
    }

    $reply = telegram_state(static function (array &$state) use ($chatId, $text): string {
        $chat = is_array($state['chats'][$chatId] ?? null) ? $state['chats'][$chatId] : [];
        $now = time();

        if ($text === '/cancel') {
            $state['chats'][$chatId] = ['authorized' => false, 'stage' => 'idle', 'attempts' => 0, 'locked_until' => 0];
            return 'Авторизация отменена. Отправьте /start, чтобы начать заново.';
        }

        if (($chat['authorized'] ?? false) === true) {
            return 'Вы авторизованы. Бот сообщит, когда с сайта поступит новая заявка.';
        }

        if ((int) ($chat['locked_until'] ?? 0) > $now) {
            return 'Слишком много попыток. Повторите вход через 15 минут.';
        }

        if ($text === '/start' || ($chat['stage'] ?? 'idle') === 'idle') {
            $chat['stage'] = 'login';
            $chat['attempts'] = (int) ($chat['attempts'] ?? 0);
            $chat['locked_until'] = 0;
            $state['chats'][$chatId] = $chat;
            return 'Введите логин:';
        }

        if (($chat['stage'] ?? '') === 'login') {
            $expectedUser = env_required('TELEGRAM_LOGIN');
            $chat['login_valid'] = hash_equals($expectedUser, $text);
            $chat['stage'] = 'password';
            $state['chats'][$chatId] = $chat;
            return 'Введите пароль:';
        }

        if (($chat['stage'] ?? '') === 'password') {
            $expectedPassword = env_required('TELEGRAM_PASSWORD');
            $valid = ($chat['login_valid'] ?? false) === true && hash_equals($expectedPassword, $text);
            if ($valid) {
                $state['chats'][$chatId] = ['authorized' => true, 'stage' => 'done', 'attempts' => 0, 'locked_until' => 0];
                return 'Готово. Теперь бот будет уведомлять вас о новых заявках с сайта.';
            }

            $attempts = (int) ($chat['attempts'] ?? 0) + 1;
            $locked = $attempts >= 5;
            $state['chats'][$chatId] = [
                'authorized' => false,
                'stage' => $locked ? 'idle' : 'login',
                'attempts' => $locked ? 0 : $attempts,
                'locked_until' => $locked ? $now + 900 : 0,
            ];
            return $locked
                ? 'Слишком много попыток. Повторите вход через 15 минут.'
                : 'Неверный логин или пароль. Введите логин ещё раз:';
        }

        $chat['stage'] = 'login';
        $state['chats'][$chatId] = $chat;
        return 'Введите логин:';
    });

    // Пароль не должен оставаться в истории чата дольше необходимого.
    $currentStage = telegram_state(static function (array &$state) use ($chatId): string {
        return (string) ($state['chats'][$chatId]['stage'] ?? '');
    });
    if ($messageId > 0 && in_array($currentStage, ['done', 'login', 'idle'], true)) {
        try {
            telegram_api('deleteMessage', ['chat_id' => $chatId, 'message_id' => $messageId]);
        } catch (Throwable) {
            // Удаление сообщения — дополнительная мера, сбой не ломает авторизацию.
        }
    }

    telegram_send_message($chatId, $reply);
    echo '{"ok":true}';
} catch (JsonException) {
    http_response_code(400);
    echo '{"ok":false}';
} catch (Throwable $error) {
    error_log('Telegram webhook error: ' . $error->getMessage());
    // Telegram не должен бесконечно повторять некорректное обновление.
    echo '{"ok":true}';
}
