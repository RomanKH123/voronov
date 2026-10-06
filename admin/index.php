<?php
// index.php - просмотр и управление заявками
require_once dirname(__DIR__) . '/api/bootstrap.php';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/admin',
    'secure' => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();
$config = appConfig();
define('ADMIN_LOGIN', $config['admin_login']);
define('ADMIN_PASSWORD_HASH', $config['admin_password_hash']);
define('ADMIN_PASSWORD', $config['admin_password']);

// Функция для логирования действий
function logAction($action, $details = '') {
    $log_file = 'admin_logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user = $_SESSION['admin_user'] ?? 'unknown';
    $log_entry = "[$timestamp] IP: $ip | User: $user | Action: $action | Details: $details" . PHP_EOL;
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Обработка выхода
if (isset($_GET['logout'])) {
    logAction('LOGOUT', 'User logged out');
    $_SESSION = array();
    session_destroy();
    header('Location: index.php');
    exit;
}

// Простая проверка аутентификации
if (!isset($_SESSION['admin_authenticated'])) {
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_form'])) {
        enforceRateLimit('admin-login', 5, 900);
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $validPassword = (ADMIN_PASSWORD_HASH !== '' && ADMIN_PASSWORD_HASH !== 'CHANGE_ME')
            ? password_verify((string)$password, ADMIN_PASSWORD_HASH)
            : (ADMIN_PASSWORD !== '' && hash_equals(ADMIN_PASSWORD, (string)$password));
        if (hash_equals(ADMIN_LOGIN, (string)$username) && $validPassword) {
            session_regenerate_id(true);
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_user'] = $username;
            $_SESSION['login_time'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            logAction('LOGIN', 'Successful login via form');
            $leadId = filter_input(INPUT_GET, 'lead', FILTER_VALIDATE_INT);
            header('Location: index.php' . ($leadId > 0 ? '?lead=' . $leadId : ''));
            exit;
        } else {
            logAction('FAILED_LOGIN', "Failed login attempt for user: $username");
            header('Location: index.php?error=1');
            exit;
        }
    }
    // Показываем форму входа
    else {
        ?>
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Вход в админ-панель</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                
                .login-container {
                    background: white;
                    padding: 40px;
                    border-radius: 20px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
                    width: 100%;
                    max-width: 400px;
                }
                
                h1 {
                    font-size: 28px;
                    color: #333;
                    margin-bottom: 10px;
                    text-align: center;
                }
                
                .subtitle {
                    color: #666;
                    text-align: center;
                    margin-bottom: 30px;
                    font-size: 14px;
                }
                
                .form-group {
                    margin-bottom: 20px;
                }
                
                label {
                    display: block;
                    margin-bottom: 8px;
                    color: #555;
                    font-weight: 500;
                    font-size: 14px;
                }
                
                input {
                    width: 100%;
                    padding: 12px 15px;
                    border: 2px solid #e0e0e0;
                    border-radius: 10px;
                    font-size: 16px;
                    transition: all 0.3s;
                }
                
                input:focus {
                    outline: none;
                    border-color: #4a6fa5;
                    box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
                }
                
                button {
                    width: 100%;
                    padding: 14px;
                    background: #4a6fa5;
                    color: white;
                    border: none;
                    border-radius: 10px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s;
                    margin-top: 10px;
                }
                
                button:hover {
                    background: #3a5a8c;
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(74, 111, 165, 0.3);
                }
                
                .error {
                    background: #fee;
                    color: #c33;
                    padding: 12px;
                    border-radius: 10px;
                    margin-bottom: 20px;
                    text-align: center;
                    border: 1px solid #fcc;
                }
                
                .info {
                    margin-top: 20px;
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 10px;
                    font-size: 13px;
                    color: #666;
                }
                
                .info-item {
                    display: flex;
                    margin-bottom: 8px;
                }
                
                .info-label {
                    width: 80px;
                    color: #999;
                }
                
                .info-value {
                    color: #333;
                    font-weight: 500;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h1>🔐 Админ-панель</h1>
                <div class="subtitle">Вход в систему управления заявками</div>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="error">
                        ❌ Неверный логин или пароль
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="login_form" value="1">
                    
                    <div class="form-group">
                        <label for="username">Логин</label>
                        <input type="text" id="username" name="username" autocomplete="username" placeholder="Введите логин" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Введите пароль" required>
                    </div>
                    
                    <button type="submit">Войти в систему</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Дополнительные проверки сессии (упрощенные)
if (isset($_SESSION['admin_authenticated'])) {
    // Автоматический выход через 8 часов
    $timeout = 28800; // 8 часов
    if (time() - $_SESSION['login_time'] > $timeout) {
        logAction('TIMEOUT', 'Session expired');
        session_destroy();
        header('Location: index.php');
        exit;
    }
    if (!hash_equals((string)($_SESSION['ip_address'] ?? ''), (string)($_SERVER['REMOTE_ADDR'] ?? '')) ||
        !hash_equals((string)($_SESSION['user_agent'] ?? ''), (string)($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
        session_destroy();
        header('Location: index.php');
        exit;
    }
}

// CSRF защита (упрощенная)
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Проверка CSRF токена
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        logAction('CSRF_ATTEMPT', 'Invalid CSRF token');
        die('Ошибка безопасности');
    }
    
    try {
        $pdo = db();
        
        if ($_POST['action'] === 'bulk_status') {
            $ids = $_POST['ids'] ?? [];
            $status = $_POST['status'] ?? '';
            if (!is_array($ids) || count($ids) < 1 || count($ids) > 100 ||
                !in_array($status, ['new', 'processed', 'completed'], true)) {
                $error = 'Выберите от 1 до 100 заявок и новый статус.';
            } else {
                $validIds = array_filter($ids, fn($value) => is_scalar($value) && filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false);
                if (count($validIds) !== count($ids)) {
                    $error = 'Некорректный номер заявки. Обновите страницу и повторите выбор.';
                } else {
                    $ids = array_values(array_unique(array_map('intval', $validIds)));
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id IN ($placeholders)");
                    $stmt->execute(array_merge([$status], $ids));
                    logAction('BULK_UPDATE_STATUS', 'IDs: ' . implode(',', $ids) . ' to status: ' . $status);
                    $_SESSION['bulk_updated'] = $stmt->rowCount();
                    header('Location: index.php?bulk_updated=1');
                    exit;
                }
            }
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if ($_POST['action'] === 'delete' && $id) {
            $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            logAction('DELETE', "Deleted application ID: $id");
            header('Location: index.php?deleted=1');
            exit;
        }
        
        if ($_POST['action'] === 'update_status' && $id && isset($_POST['status'])) {
            $allowed_statuses = ['new', 'processed', 'completed'];
            $status = $_POST['status'];
            
            if (in_array($status, $allowed_statuses, true)) {
                $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                logAction('UPDATE_STATUS', "Updated application ID: $id to status: $status");
                header('Location: index.php?updated=1');
                exit;
            }
        }
    } catch (PDOException $e) {
        logAction('DB_ERROR', $e->getMessage());
        $error = 'Ошибка базы данных';
    }
}

// Получение данных
try {
    $pdo = db();
    
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY 
        CASE status 
            WHEN 'new' THEN 1 
            WHEN 'processed' THEN 2 
            ELSE 3 
        END, created_at DESC");
    $applications = $stmt->fetchAll();
    
    // Статистика
    $total = count($applications);
    $new = count(array_filter($applications, fn($a) => $a['status'] == 'new'));
    $processed = count(array_filter($applications, fn($a) => $a['status'] == 'processed'));
    $completed = count(array_filter($applications, fn($a) => $a['status'] == 'completed'));
    $today = count(array_filter($applications, fn($a) => date('Y-m-d', strtotime($a['created_at'])) == date('Y-m-d')));
    
} catch (PDOException $e) {
    logAction('DB_CONNECTION_ERROR', $e->getMessage());
    die('Ошибка подключения к базе данных');
}

// Генерируем CSRF токен
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="voronov-std" />
    <link rel="manifest" href="/favicon/site.webmanifest" />
    <title>Админ-панель - Заявки с сайта</title>
    <link rel="stylesheet" href="admin.css?v=<?php echo filemtime(__DIR__ . '/admin.css'); ?>">
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['bulk_updated'])): ?>
            <div class="notification" role="status">Статус изменён у заявок: <?php echo (int)$_SESSION['bulk_updated']; ?>. Заявки с выбранным статусом остались без изменений.</div>
            <?php unset($_SESSION['bulk_updated']); ?>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="notification" role="status">✅ Заявка успешно удалена</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="notification" role="status">✅ Статус заявки обновлен</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error" role="alert">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="header">
            <div class="header-top">
                <div><div class="eyebrow">VORONOV / УПРАВЛЕНИЕ</div><h1>Заявки с сайта</h1>
                    <p class="subtitle">Перетаскивайте карточки между колонками или меняйте статус у нескольких заявок сразу.</p></div>
                
                <div class="user-menu">
                    <a href="/" class="site-link" target="_blank" rel="noopener">На сайт ↗</a><span class="username"> <?php echo htmlspecialchars($_SESSION['admin_user']); ?></span>
                    <a href="?logout=1" class="logout-btn">Выйти</a>
                </div>
            </div>
            
            <div class="stats-grid">
                <button type="button" class="stat-card" data-summary="all" aria-pressed="false">
                    <div class="stat-value"><?php echo $total; ?></div>
                    <div class="stat-label">Всего заявок</div>
                </button>
                <button type="button" class="stat-card" data-summary="new" aria-pressed="false">
                    <div class="stat-value"><?php echo $new; ?></div>
                    <div class="stat-label">Новые</div>
                </button>
                <button type="button" class="stat-card" data-summary="processed" aria-pressed="false">
                    <div class="stat-value"><?php echo $processed; ?></div>
                    <div class="stat-label">В обработке</div>
                </button>
                <button type="button" class="stat-card" data-summary="completed" aria-pressed="false">
                    <div class="stat-value"><?php echo $completed; ?></div>
                    <div class="stat-label">Завершенные</div>
                </button>
                <button type="button" class="stat-card" data-summary="today" aria-pressed="false">
                    <div class="stat-value"><?php echo $today; ?></div>
                    <div class="stat-label">За сегодня</div>
                </button>
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">Все</button>
                <button class="filter-btn" data-filter="new">Новые</button>
                <button class="filter-btn" data-filter="processed">В обработке</button>
                <button class="filter-btn" data-filter="completed">Завершенные</button>
            </div>
            
            <div class="filter-fields">
                <label class="filter-field search-box" for="search">Поиск заявок
                    <input type="search" id="search" placeholder="Имя, телефон, email, сообщение или № заявки" autocomplete="off">
                </label>
                <label class="filter-field" for="period">Период
                    <select id="period"><option value="all">За всё время</option><option value="today">Сегодня</option><option value="week">Последние 7 дней</option><option value="month">Последние 30 дней</option></select>
                </label>
                <label class="filter-field" for="sort">Порядок
                    <select id="sort"><option value="priority">Сначала новые заявки</option><option value="newest">Сначала свежие</option><option value="oldest">Сначала старые</option></select>
                </label>
                <button type="button" id="resetFilters">Сбросить</button>
            </div>
        </div>
        <div class="results-bar">
            <p id="resultCount" role="status" aria-live="polite"></p>
            <label for="pageSize">Карточек в колонке <select id="pageSize"><option value="20">20</option><option value="50">50</option><option value="100">100</option></select></label>
        </div>
        <form method="POST" id="bulkForm" class="bulk-bar">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="bulk_status">
            <label class="select-page"><input type="checkbox" id="selectPage"> Все видимые</label>
            <strong id="selectionCount" role="status">Выбрано: 0</strong>
            <label for="bulkStatus">Перенести в</label>
            <select name="status" id="bulkStatus" required>
                <option value="" selected disabled>Выберите статус</option>
                <option value="new">Новые</option><option value="processed">В обработке</option><option value="completed">Завершённые</option>
            </select>
            <button type="submit" id="bulkSubmit" disabled>Применить к выбранным</button>
            <button type="button" id="clearSelection" disabled>Снять выбор</button>
            <span class="selection-hint">До 100 заявок за раз. При смене фильтра или страницы выбор сбрасывается.</span>
        </form>
        <noscript><p>Для поиска и массового переноса заявок включите JavaScript. Статус отдельной заявки можно сохранить кнопкой в строке.</p></noscript>
        <form method="POST" id="moveForm" hidden>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id"><input type="hidden" name="status">
        </form>
        <div id="kanban" class="kanban" aria-label="Заявки по статусам" hidden>
            <?php foreach (['new' => 'Новые', 'processed' => 'В обработке', 'completed' => 'Завершённые'] as $stage => $label): ?>
                <section class="kanban-column stage-<?php echo $stage; ?>" data-stage="<?php echo $stage; ?>" aria-labelledby="stage-<?php echo $stage; ?>">
                    <h2 id="stage-<?php echo $stage; ?>"><?php echo $label; ?> <span class="stage-count">0</span></h2>
                    <div class="stage-cards"></div>
                </section>
            <?php endforeach; ?>
        </div>
        <div class="table-container">
            <table id="applicationsTable"><caption class="sr-only">Заявки клиентов</caption>
                <thead>
                    <tr>
                        <th class="selection-cell"><span class="sr-only">Выбор заявки</span></th><th scope="col">ID</th>
                        <th>Дата</th>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th class="message-cell">Сообщение</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr data-id="<?php echo (int)$app['id']; ?>" data-status="<?php echo htmlspecialchars($app['status']); ?>" class="status-<?php echo htmlspecialchars($app['status']); ?>">
                        <td class="selection-cell"><input type="checkbox" name="ids[]" form="bulkForm" value="<?php echo (int)$app['id']; ?>" class="row-select" aria-label="Выбрать заявку №<?php echo (int)$app['id']; ?>"></td>
                        <td class="id-cell">#<?php echo (int)$app['id']; ?></td>
                        <td class="date-cell"><?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?></td>
                        <td class="name-cell"><?php echo htmlspecialchars($app['name']); ?></td>
                        <td class="phone-cell">
                            <a href="tel:<?php echo htmlspecialchars($app['phone']); ?>">
                                <?php echo htmlspecialchars($app['phone']); ?>
                            </a>
                        </td>
                        <td class="email-cell">
                            <?php if (!empty($app['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($app['email']); ?>">
                                    <?php echo htmlspecialchars($app['email']); ?>
                                </a>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="message-cell">
                            <?php if (!empty($app['message'])): ?>
                                <button type="button" class="message-preview" onclick="showMessage(<?php echo (int)$app['id']; ?>)" aria-label="Открыть сообщение заявки №<?php echo (int)$app['id']; ?>">
                                    <?php echo htmlspecialchars(mb_substr($app['message'], 0, 45)); ?>
                                </button>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="status-cell">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?php echo (int)$app['id']; ?>">
                                <select aria-label="Статус заявки №<?php echo (int)$app['id']; ?>" name="status" onchange="this.form.submit()" class="status-badge <?php echo htmlspecialchars($app['status']); ?>">
                                    <option value="new" <?php echo $app['status'] == 'new' ? 'selected' : ''; ?>>Новая</option>
                                    <option value="processed" <?php echo $app['status'] == 'processed' ? 'selected' : ''; ?>>В обработке</option>
                                    <option value="completed" <?php echo $app['status'] == 'completed' ? 'selected' : ''; ?>>Завершена</option>
                                </select><noscript><button type="submit">Сохранить</button></noscript>
                            </form>
                        </td>
                        <td class="actions-cell">
                            <div class="action-buttons">
                                <button onclick="viewDetails(<?php echo (int)$app['id']; ?>)" class="btn btn-view" title="Просмотр">Открыть</button>
                                <button onclick="deleteApplication(<?php echo (int)$app['id']; ?>)" class="btn btn-delete" title="Удалить">Удалить</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="emptyState" class="empty-state" hidden><h2 id="emptyTitle">Заявки не найдены</h2><p id="emptyText">Измените запрос или сбросьте фильтры.</p><button type="button" id="emptyReset">Сбросить фильтры</button></div>
        </div>
        <nav class="pagination" aria-label="Страницы заявок"><button type="button" id="prevPage">← Назад</button><span id="pageInfo"></span><button type="button" id="nextPage">Далее →</button></nav>
    </div>
    
    <!-- Модальные окна -->
    <div id="messageModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="messageTitle" tabindex="-1">
        <div class="modal-content">
            <h3 class="modal-title" id="messageTitle">📝 Сообщение</h3>
            <div id="messageContent" class="modal-value"></div>
            <div class="modal-buttons">
                <button class="modal-btn cancel" onclick="closeMessageModal()">Закрыть</button>
            </div>
        </div>
    </div>
    
    <div id="detailsModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="detailsTitle" tabindex="-1">
        <div class="modal-content">
            <h3 class="modal-title" id="detailsTitle">🔍 Детали заявки</h3>
            <div id="detailsContent"></div>
            <div class="modal-buttons">
                <button type="button" id="detailsDelete" class="btn-delete" onclick="deleteApplication(this.dataset.id)">Удалить заявку</button>
                <button class="modal-btn cancel" onclick="closeDetailsModal()">Закрыть</button>
            </div>
        </div>
    </div>
    
    <div id="deleteModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="deleteTitle" tabindex="-1">
        <div class="modal-content">
            <h3 class="modal-title" id="deleteTitle">Подтверждение удаления</h3>
            <p id="deleteDescription"></p><p>Это действие нельзя отменить.</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <div class="modal-buttons">
                    <button type="submit" class="modal-btn confirm">Удалить</button>
                    <button type="button" class="modal-btn cancel" onclick="closeModal()">Отмена</button>
                </div>
            </form>
        </div>
    </div>
    
    <script id="applicationsData" type="application/json"><?php echo json_encode($applications, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?></script>
    <script src="admin.js?v=<?php echo filemtime(__DIR__ . '/admin.js'); ?>" defer></script>
</body>
</html>
