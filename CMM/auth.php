<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/api/bootstrap.php';

// Binding to the current password hash and role revokes older sessions without a schema migration.
function cmmAuthFingerprint(array $user): string
{
    return hash('sha256', json_encode([
        (int)$user['id'], (string)$user['login'], (string)$user['password_hash'], (string)$user['role'],
    ], JSON_THROW_ON_ERROR));
}

function cmmClearAuth(): void
{
    foreach (array_keys($_SESSION) as $key) {
        if (str_starts_with((string)$key, 'cmm_')) unset($_SESSION[$key]);
    }
}

function cmmSessionValid(PDO $pdo): bool
{
    if (($_SESSION['cmm_auth'] ?? false) !== true || empty($_SESSION['cmm_user_id']) ||
        !is_string($_SESSION['cmm_auth_fingerprint'] ?? null)) {
        cmmClearAuth();
        return false;
    }
    $stmt = $pdo->prepare('SELECT id, login, password_hash, name, role FROM cmm_users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int)$_SESSION['cmm_user_id']]);
    $user = $stmt->fetch();
    if (!$user || !in_array($user['role'], ['admin', 'editor'], true) ||
        !hash_equals(cmmAuthFingerprint($user), $_SESSION['cmm_auth_fingerprint'])) {
        cmmClearAuth();
        return false;
    }
    $_SESSION['cmm_user_login'] = $user['login'];
    $_SESSION['cmm_user_name'] = $user['name'];
    $_SESSION['cmm_user_role'] = $user['role'];
    return true;
}

function cmmRequireAuth(): PDO
{
    startSecureSession('/CMM');
    if (($_SESSION['cmm_auth'] ?? false) !== true) {
        jsonResponse(['success' => false, 'message' => 'Не авторизован'], 401);
    }
    try {
        $pdo = db();
        $valid = cmmSessionValid($pdo);
    } catch (Throwable $error) {
        error_log('CMM session verification unavailable');
        jsonResponse(['success' => false, 'message' => 'Сервис временно недоступен'], 503);
    }
    if (!$valid) jsonResponse(['success' => false, 'message' => 'Сессия завершена. Войдите снова.'], 401);
    return $pdo;
}
