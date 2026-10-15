<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/server/bootstrap.php';
require_https();
security_headers(true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$allowedIps = array_filter(array_map('trim', explode(',', getenv('ADMIN_ALLOWED_IPS') ?: '')));
if ($allowedIps && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIps, true)) {
    http_response_code(404);
    exit('Not found');
}

start_admin_session();

$now = time();
$idleTimeout = 1800;
$absoluteTimeout = 28800;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) {
    http_response_code(413);
    exit('Request too large');
}

if (!empty($_SESSION['authenticated'])) {
    try {
        $currentAuthVersion = hash('sha256', env_required('ADMIN_PASSWORD_HASH'));
        if (!isset($_SESSION['auth_version']) || !hash_equals($_SESSION['auth_version'], $currentAuthVersion)) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
    } catch (Throwable) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}

if (!empty($_SESSION['authenticated'])) {
    $expiredByIdle = $now - (int) ($_SESSION['last_activity'] ?? 0) > $idleTimeout;
    $expiredAbsolutely = $now - (int) ($_SESSION['login_at'] ?? 0) > $absoluteTimeout;
    if ($expiredByIdle || $expiredAbsolutely) {
        $_SESSION = [];
        session_regenerate_id(true);
    } else {
        $_SESSION['last_activity'] = $now;
        if ($now - (int) ($_SESSION['regenerated_at'] ?? 0) > 600) {
            session_regenerate_id(true);
            $_SESSION['regenerated_at'] = $now;
        }
    }
}

$error = '';
$notice = '';
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        http_response_code(403);
        $error = 'Сессия устарела. Обновите страницу и повторите действие.';
    } elseif ($action === 'login' && empty($_SESSION['authenticated'])) {
        try {
            if (!rate_limit('admin_login', 5, 900)) {
                http_response_code(429);
                $error = 'Слишком много попыток. Повторите вход через 15 минут.';
            } else {
                $expectedUser = getenv('ADMIN_USER') ?: 'admin';
                $expectedHash = env_required('ADMIN_PASSWORD_HASH');
                if (empty(password_get_info($expectedHash)['algo'])) {
                    throw new RuntimeException('ADMIN_PASSWORD_HASH is invalid.');
                }
                $username = trim((string) ($_POST['username'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $validUser = hash_equals($expectedUser, $username);
                $validPassword = password_verify($password, $expectedHash);

                if ($validUser && $validPassword) {
                    session_regenerate_id(true);
                    $_SESSION['authenticated'] = true;
                    $_SESSION['login_at'] = $now;
                    $_SESSION['last_activity'] = $now;
                    $_SESSION['regenerated_at'] = $now;
                    $_SESSION['auth_version'] = hash('sha256', $expectedHash);
                    $_SESSION['csrf'] = bin2hex(random_bytes(32));
                    header('Location: ./', true, 303);
                    exit;
                }

                usleep(random_int(250000, 500000));
                $error = 'Неверный логин или пароль.';
            }
        } catch (Throwable $exception) {
            error_log('Admin login error: ' . $exception->getMessage());
            $error = 'Админ-панель не настроена на сервере.';
        }
    } elseif ($action === 'logout' && !empty($_SESSION['authenticated'])) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], '', $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: ./', true, 303);
        exit;
    } elseif ($action === 'status' && !empty($_SESSION['authenticated'])) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $status = (string) ($_POST['status'] ?? '');
        if (!$id || !in_array($status, ['new', 'contacted', 'closed'], true)) {
            $error = 'Некорректные данные заявки.';
        } else {
            try {
                $statement = db()->prepare('UPDATE leads SET status = :status WHERE id = :id');
                $statement->execute(['status' => $status, 'id' => $id]);
                $notice = 'Статус заявки обновлён.';
            } catch (Throwable $exception) {
                error_log('Admin update error: ' . $exception->getMessage());
                $error = 'Не удалось обновить заявку.';
            }
        }
    }
}

$authenticated = !empty($_SESSION['authenticated']);
$leads = [];
if ($authenticated) {
    try {
        $leads = db()->query(
            'SELECT id, package, name, phone, status, created_at FROM leads ORDER BY created_at DESC, id DESC LIMIT 500'
        )->fetchAll();
    } catch (Throwable $exception) {
        error_log('Admin list error: ' . $exception->getMessage());
        $error = 'Не удалось загрузить заявки. Проверьте подключение к MySQL.';
    }
}

$statusLabels = ['new' => 'Новая', 'contacted' => 'Связались', 'closed' => 'Закрыта'];

function format_admin_date(string $value): string
{
    try {
        return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('Europe/Moscow'))
            ->format('d.m.Y H:i');
    } catch (Throwable) {
        return $value;
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Админ-панель — Ежевика</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<main class="admin-shell">
<?php if (!$authenticated): ?>
    <section class="login-card">
        <h1>Вход в админ-панель</h1>
        <p>Управление заявками лофта «Ежевика»</p>
        <?php if ($error !== ''): ?><div class="message message--error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" autocomplete="on">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label>
                <span>Логин</span>
                <input type="text" name="username" autocomplete="username" required autofocus maxlength="80">
            </label>
            <label>
                <span>Пароль</span>
                <input type="password" name="password" autocomplete="current-password" required maxlength="200">
            </label>
            <button type="submit">Войти</button>
        </form>
    </section>
<?php else: ?>
    <header class="admin-header">
        <div>
            <h1>Заявки</h1>
            <p>Последние 500 обращений</p>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="logout">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <button class="button-secondary" type="submit">Выйти</button>
        </form>
    </header>

    <?php if ($error !== ''): ?><div class="message message--error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($notice !== ''): ?><div class="message message--success"><?= e($notice) ?></div><?php endif; ?>

    <section class="leads-card">
        <?php if (!$leads): ?>
            <p class="empty-state">Заявок пока нет.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Дата</th><th>Вариант</th><th>Клиент</th><th>Телефон</th><th>Статус</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><?= e(format_admin_date((string) $lead['created_at'])) ?></td>
                            <td><?= e((string) $lead['package']) ?></td>
                            <td><?= e((string) $lead['name']) ?></td>
                            <td><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) $lead['phone'])) ?>"><?= e((string) $lead['phone']) ?></a></td>
                            <td>
                                <form class="status-form" method="post">
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $lead['id'] ?>">
                                    <select name="status" aria-label="Статус заявки">
                                    <?php foreach ($statusLabels as $value => $label): ?>
                                        <option value="<?= e($value) ?>"<?= $lead['status'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                    </select>
                                    <button type="submit">Сохранить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
</main>
</body>
</html>
