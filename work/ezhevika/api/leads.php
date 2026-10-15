<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/server/bootstrap.php';
require_once dirname(__DIR__) . '/telegram/bot.php';
require_https();
security_headers();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        respond(405, ['ok' => false, 'message' => 'Method not allowed']);
    }

    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength < 2 || $contentLength > 4096) {
        respond(413, ['ok' => false, 'message' => 'Invalid request size']);
    }

    $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($contentType !== 'application/json') {
        respond(415, ['ok' => false, 'message' => 'JSON required']);
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '') {
        $originHost = parse_url($origin, PHP_URL_HOST);
        $requestHost = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
        if (!is_string($originHost) || !hash_equals(strtolower($requestHost), strtolower($originHost))) {
            respond(403, ['ok' => false, 'message' => 'Origin rejected']);
        }
    }

    if (!rate_limit('lead', 5, 3600)) {
        respond(429, ['ok' => false, 'message' => 'Слишком много заявок. Позвоните нам по телефону.']);
    }

    $input = json_decode((string) file_get_contents('php://input'), true, 8, JSON_THROW_ON_ERROR);
    if (!is_array($input)) {
        respond(400, ['ok' => false, 'message' => 'Invalid request']);
    }

    // Honeypot: обычный пользователь это поле не видит и не заполняет.
    if (!empty($input['website'])) {
        respond(200, ['ok' => true]);
    }

    $package = trim((string) ($input['package'] ?? ''));
    $name = trim((string) ($input['name'] ?? ''));
    $phone = trim((string) ($input['phone'] ?? ''));
    $allowedPackages = [
        'Аренда лофта',
        'Ежевика MINI',
        'Ежевика LIGHT',
        'Ежевика',
        'Ежевика PARTY',
        'Ежевика PARTY VIP',
        'Анимация',
    ];

    if (!in_array($package, $allowedPackages, true)) {
        respond(422, ['ok' => false, 'message' => 'Выберите вариант']);
    }
    $nameLength = preg_match_all('/./us', $name, $unusedMatches);
    if ($name === '' || $nameLength === false || $nameLength > 80 || preg_match('/[<>]/u', $name)) {
        respond(422, ['ok' => false, 'message' => 'Проверьте имя']);
    }
    if (!preg_match('/^[0-9+()\-\s]{7,30}$/', $phone)) {
        respond(422, ['ok' => false, 'message' => 'Проверьте номер телефона']);
    }

    $statement = db()->prepare(
        "INSERT INTO leads(package, name, phone, status, created_at, ip_hash, user_agent)
         VALUES(:package, :name, :phone, 'new', UTC_TIMESTAMP(), :ip_hash, :user_agent)"
    );
    $statement->execute([
        'package' => $package,
        'name' => $name,
        'phone' => $phone,
        'ip_hash' => client_identifier(),
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);

    try {
        telegram_notify_new_lead();
    } catch (Throwable $notificationError) {
        error_log('Telegram notification error: ' . $notificationError->getMessage());
    }

    respond(201, ['ok' => true]);
} catch (JsonException) {
    respond(400, ['ok' => false, 'message' => 'Invalid JSON']);
} catch (Throwable $error) {
    error_log('Lead endpoint error: ' . $error->getMessage());
    respond(500, ['ok' => false, 'message' => 'Сервис временно недоступен']);
}
