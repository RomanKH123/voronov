<?php
declare(strict_types=1);

ini_set('display_errors', '0');
header_remove('X-Powered-By');

function load_env_file(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $separator = strpos($line, '=');
        if ($separator === false) {
            continue;
        }

        $name = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $name) || getenv($name) !== false) {
            continue;
        }

        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
    }
}

load_env_file(dirname(__DIR__) . '/.env');

function env_required(string $name): string
{
    $value = getenv($name);
    if ($value === false || trim($value) === '') {
        throw new RuntimeException("Missing required environment variable: {$name}");
    }

    return trim($value);
}

function security_headers(bool $html = false): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    if ($html) {
        header("Content-Security-Policy: default-src 'self'; style-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'none'; object-src 'none'");
    } else {
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'");
    }
}

function require_https(): void
{
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if (!$secure && getenv('ALLOW_INSECURE_HTTP') !== '1') {
        http_response_code(426);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'HTTPS required';
        exit;
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env_required('DB_HOST');
    $port = getenv('DB_PORT') ?: '3306';
    $database = env_required('DB_NAME');
    $username = env_required('DB_USER');
    $password = env_required('DB_PASSWORD');
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function client_identifier(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $secret = env_required('APP_SECRET');
    if (strlen($secret) < 32) {
        throw new RuntimeException('APP_SECRET must contain at least 32 characters.');
    }
    return hash_hmac('sha256', $ip, $secret);
}

function rate_limit(string $scope, int $limit, int $windowSeconds): bool
{
    $pdo = db();
    $identifier = client_identifier();
    $cutoff = time() - $windowSeconds;

    $pdo->beginTransaction();
    try {
        $cleanup = $pdo->prepare('DELETE FROM rate_limits WHERE created_at < :cutoff');
        $cleanup->execute(['cutoff' => $cutoff]);

        $count = $pdo->prepare('SELECT COUNT(*) FROM rate_limits WHERE scope = :scope AND identifier = :identifier AND created_at >= :cutoff FOR UPDATE');
        $count->execute(['scope' => $scope, 'identifier' => $identifier, 'cutoff' => $cutoff]);
        $allowed = (int) $count->fetchColumn() < $limit;

        if ($allowed) {
            $insert = $pdo->prepare('INSERT INTO rate_limits(scope, identifier, created_at) VALUES(:scope, :identifier, :created_at)');
            $insert->execute(['scope' => $scope, 'identifier' => $identifier, 'created_at' => time()]);
        }

        $pdo->commit();
        return $allowed;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_name('ezhevika_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/admin',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
