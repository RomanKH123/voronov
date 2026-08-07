<?php
declare(strict_types=1);

function appConfig(): array
{
    static $config;
    if (isset($config)) {
        return $config;
    }

    $localFile = dirname(__DIR__) . '/config.local.php';
    $local = is_file($localFile) ? require $localFile : [];
    $config = [
        'db_host' => getenv('DB_HOST') ?: ($local['db_host'] ?? ''),
        'db_name' => getenv('DB_NAME') ?: ($local['db_name'] ?? ''),
        'db_user' => getenv('DB_USER') ?: ($local['db_user'] ?? ''),
        'db_pass' => getenv('DB_PASS') ?: ($local['db_pass'] ?? ''),
        'admin_login' => getenv('ADMIN_LOGIN') ?: ($local['admin_login'] ?? 'admin'),
        'admin_password_hash' => getenv('ADMIN_PASSWORD_HASH') ?: ($local['admin_password_hash'] ?? ''),
        'admin_password' => getenv('ADMIN_PASSWORD') ?: ($local['admin_password'] ?? ''),
        'allowed_origin' => rtrim(getenv('ALLOWED_ORIGIN') ?: ($local['allowed_origin'] ?? 'https://voronov-art.ru'), '/'),
    ];
    return $config;
}

function db(): PDO
{
    $config = appConfig();
    foreach (['db_host', 'db_name', 'db_user', 'db_pass'] as $key) {
        if ($config[$key] === '' || $config[$key] === 'CHANGE_ME') {
            throw new RuntimeException('Server configuration is incomplete');
        }
    }
    return new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}

function securityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
}

function startSecureSession(string $path = '/'): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $path,
        'secure' => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function jsonResponse(array $body, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function enforceSameOrigin(): void
{
    $origin = rtrim((string)($_SERVER['HTTP_ORIGIN'] ?? ''), '/');
    $refererHost = parse_url((string)($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST);
    $allowed = appConfig()['allowed_origin'];
    $allowedHost = parse_url($allowed, PHP_URL_HOST);
    if (($origin !== '' && $origin !== $allowed) ||
        ($origin === '' && (!$refererHost || $refererHost !== $allowedHost))) {
        jsonResponse(['success' => false, 'message' => 'Источник запроса не разрешён'], 403);
    }
}

function enforceRateLimit(string $scope, int $limit = 8, int $window = 600): void
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $file = sys_get_temp_dir() . '/voronov_' . hash('sha256', $scope . '|' . $ip) . '.json';
    $now = time();
    $attempts = [];
    $handle = @fopen($file, 'c+');
    if (!$handle) {
        return;
    }
    try {
        if (flock($handle, LOCK_EX)) {
            $raw = stream_get_contents($handle);
            $stored = $raw ? json_decode($raw, true) : [];
            $attempts = array_values(array_filter(is_array($stored) ? $stored : [], fn($t) => is_int($t) && $t > $now - $window));
            if (count($attempts) >= $limit) {
                flock($handle, LOCK_UN);
                jsonResponse(['success' => false, 'message' => 'Слишком много запросов. Повторите позже.'], 429);
            }
            $attempts[] = $now;
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($attempts));
            fflush($handle);
            flock($handle, LOCK_UN);
        }
    } finally {
        fclose($handle);
    }
}

function cleanText(mixed $value, int $maxLength): string
{
    $value = trim((string)$value);
    return mb_substr(strip_tags($value), 0, $maxLength, 'UTF-8');
}

securityHeaders();
