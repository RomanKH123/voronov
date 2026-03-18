<?php
// index.php - просмотр и управление заявками
session_start();

$db_host = 'localhost';
$db_name = 'vh384894_voronov';
$db_user = 'vh384894_voronov';
$db_pass = 'voronov20032003';

// Фиксированные учетные данные (без хеширования для простоты)
define('ADMIN_LOGIN', 'admin');
define('ADMIN_PASSWORD', '093093093Rk');

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
    
    // Проверка Basic Auth (для обратной совместимости)
    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
        if ($_SERVER['PHP_AUTH_USER'] === ADMIN_LOGIN && $_SERVER['PHP_AUTH_PW'] === ADMIN_PASSWORD) {
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_user'] = ADMIN_LOGIN;
            $_SESSION['login_time'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            logAction('LOGIN', 'Successful login via Basic Auth');
        } else {
            header('WWW-Authenticate: Basic realm="Admin Panel"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Неверные учетные данные';
            exit;
        }
    }
    // Проверка формы входа
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_form'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username === ADMIN_LOGIN && $password === ADMIN_PASSWORD) {
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_user'] = $username;
            $_SESSION['login_time'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            logAction('LOGIN', 'Successful login via form');
            header('Location: index.php');
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
                        <input type="text" id="username" name="username" value="admin" placeholder="Введите логин" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" placeholder="Введите пароль" required>
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
}

// CSRF защита (упрощенная)
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && $token === $_SESSION['csrf_token'];
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Проверка CSRF токена
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        logAction('CSRF_ATTEMPT', 'Invalid CSRF token');
        die('Ошибка безопасности');
    }
    
    try {
        $pdo = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
            $db_user,
            $db_pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
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
            
            if (in_array($status, $allowed_statuses)) {
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
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        h1 {
            font-size: 32px;
            color: #000;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        h1 span {
            background: #4a6fa5;
            color: white;
            font-size: 14px;
            padding: 5px 15px;
            border-radius: 30px;
            font-weight: normal;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .username {
            background: #4a6fa5;
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #4a6fa5;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .notification {
            background: #4CAF50;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            animation: slideDown 0.5s;
        }
        
        .notification.error {
            background: #dc3545;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .filters {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: #4a6fa5;
            border-color: #4a6fa5;
            color: white;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .search-box::before {
            content: '🔍';
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .table-container {
            background: white;
            border-radius: 20px;
            overflow-x: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }
        
        th {
            background: #000;
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            vertical-align: middle;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        tr.status-new {
            background-color: #fff9e6;
        }
        
        tr.status-processed {
            background-color: #e6f3ff;
        }
        
        tr.status-completed {
            background-color: #e6ffe6;
        }
        
        .id-cell {
            font-weight: bold;
            color: #4a6fa5;
        }
        
        .date-cell {
            white-space: nowrap;
            font-size: 13px;
            color: #666;
        }
        
        .phone-cell a {
            color: #4a6fa5;
            text-decoration: none;
            font-weight: 500;
        }
        
        .email-cell a {
            color: #4a6fa5;
            text-decoration: none;
        }
        
        .message-cell {
            max-width: 200px;
            cursor: pointer;
            color: #666;
        }
        
        .message-preview {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .form-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .form-type.modal {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .form-type.main {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .ip-cell {
            font-family: monospace;
            font-size: 12px;
            color: #666;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
        }
        
        .status-badge.new {
            background: #ffeb3b;
            color: #000;
        }
        
        .status-badge.processed {
            background: #4a6fa5;
            color: white;
        }
        
        .status-badge.completed {
            background: #4CAF50;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn-view {
            background: #4a6fa5;
            color: white;
        }
        
        .btn-view:hover {
            background: #3a5a8c;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #000;
        }
        
        .modal-label {
            font-weight: 600;
            color: #4a6fa5;
            margin-bottom: 5px;
            margin-top: 15px;
        }
        
        .modal-value {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            word-break: break-word;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        
        .modal-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            flex: 1;
        }
        
        .modal-btn.confirm {
            background: #dc3545;
            color: white;
        }
        
        .modal-btn.cancel {
            background: #6c757d;
            color: white;
        }
        
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-buttons {
                display: flex;
                gap: 10px;
            }
            
            .filter-btn {
                flex: 1;
            }
            
            .header-top {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_GET['deleted'])): ?>
            <div class="notification">✅ Заявка успешно удалена</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="notification">✅ Статус заявки обновлен</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="header">
            <div class="header-top">
                <h1>
                    📋 Админ-панель заявок
                   
                </h1>
                
                <div class="user-menu">
                    <span class="username">👤 <?php echo htmlspecialchars($_SESSION['admin_user']); ?></span>
                    <a href="?logout=1" class="logout-btn">🚪 Выйти</a>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total; ?></div>
                    <div class="stat-label">Всего заявок</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $new; ?></div>
                    <div class="stat-label">Новые</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $processed; ?></div>
                    <div class="stat-label">В обработке</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $completed; ?></div>
                    <div class="stat-label">Завершенные</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $today; ?></div>
                    <div class="stat-label">За сегодня</div>
                </div>
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">Все</button>
                <button class="filter-btn" data-filter="new">Новые</button>
                <button class="filter-btn" data-filter="processed">В обработке</button>
                <button class="filter-btn" data-filter="completed">Завершенные</button>
            </div>
            
            <div class="search-box">
                <input type="text" id="search" placeholder="Поиск по имени, телефону или email...">
            </div>
        </div>
        
        <div class="table-container">
            <table id="applicationsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Сообщение</th>
                        <th>Форма</th>
                        <th>IP</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr data-status="<?php echo htmlspecialchars($app['status']); ?>" class="status-<?php echo htmlspecialchars($app['status']); ?>">
                        <td class="id-cell">#<?php echo (int)$app['id']; ?></td>
                        <td class="date-cell"><?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?></td>
                        <td class="name-cell"><?php echo htmlspecialchars(mb_substr($app['name'], 0, 30)); ?></td>
                        <td class="phone-cell">
                            <a href="tel:<?php echo htmlspecialchars($app['phone']); ?>">
                                <?php echo htmlspecialchars($app['phone']); ?>
                            </a>
                        </td>
                        <td class="email-cell">
                            <?php if (!empty($app['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($app['email']); ?>">
                                    <?php echo htmlspecialchars(mb_substr($app['email'], 0, 25)); ?>
                                </a>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="message-cell" onclick="showMessage(<?php echo htmlspecialchars(json_encode($app['message'])); ?>)">
                            <?php if (!empty($app['message'])): ?>
                                <div class="message-preview">
                                    <?php echo htmlspecialchars(mb_substr($app['message'], 0, 30)); ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="form-type <?php echo htmlspecialchars($app['form_type']); ?>">
                                <?php echo $app['form_type'] == 'modal' ? '📱 Мод' : '📝 Осн'; ?>
                            </span>
                        </td>
                        <td class="ip-cell">
                            <?php echo htmlspecialchars($app['ip_address'] ?? '—'); ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?php echo (int)$app['id']; ?>">
                                <select name="status" onchange="this.form.submit()" class="status-badge <?php echo htmlspecialchars($app['status']); ?>">
                                    <option value="new" <?php echo $app['status'] == 'new' ? 'selected' : ''; ?>>Новая</option>
                                    <option value="processed" <?php echo $app['status'] == 'processed' ? 'selected' : ''; ?>>В обработке</option>
                                    <option value="completed" <?php echo $app['status'] == 'completed' ? 'selected' : ''; ?>>Завершена</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button onclick="viewDetails(<?php echo (int)$app['id']; ?>)" class="btn btn-view" title="Просмотр">👁️</button>
                                <button onclick="deleteApplication(<?php echo (int)$app['id']; ?>)" class="btn btn-delete" title="Удалить">🗑️</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Модальные окна -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">📝 Сообщение</h3>
            <div id="messageContent" class="modal-value"></div>
            <div class="modal-buttons">
                <button class="modal-btn cancel" onclick="closeMessageModal()">Закрыть</button>
            </div>
        </div>
    </div>
    
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">🔍 Детали заявки</h3>
            <div id="detailsContent"></div>
            <div class="modal-buttons">
                <button class="modal-btn cancel" onclick="closeDetailsModal()">Закрыть</button>
            </div>
        </div>
    </div>
    
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Подтверждение удаления</h3>
            <p>Вы уверены, что хотите удалить эту заявку?</p>
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
    
    <script>
        const applications = <?php echo json_encode($applications, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        
        // Фильтрация
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                const rows = document.querySelectorAll('#applicationsTable tbody tr');
                
                rows.forEach(row => {
                    row.style.display = (filter === 'all' || row.dataset.status === filter) ? '' : 'none';
                });
            });
        });
        
        // Поиск
        document.getElementById('search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#applicationsTable tbody tr');
            
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Просмотр сообщения
        function showMessage(message) {
            if (!message || message === 'null') {
                alert('Сообщение отсутствует');
                return;
            }
            document.getElementById('messageContent').innerHTML = '<p>' + message.replace(/\n/g, '<br>') + '</p>';
            document.getElementById('messageModal').classList.add('show');
        }
        
        function closeMessageModal() {
            document.getElementById('messageModal').classList.remove('show');
        }
        
        // Просмотр деталей
        function viewDetails(id) {
            const app = applications.find(a => a.id == id);
            if (!app) return;
            
            const details = `
                <div class="modal-label">ID:</div>
                <div class="modal-value">#${app.id}</div>
                <div class="modal-label">Имя:</div>
                <div class="modal-value">${escapeHtml(app.name) || '-'}</div>
                <div class="modal-label">Телефон:</div>
                <div class="modal-value">${escapeHtml(app.phone) || '-'}</div>
                <div class="modal-label">Email:</div>
                <div class="modal-value">${escapeHtml(app.email) || '-'}</div>
                <div class="modal-label">Сообщение:</div>
                <div class="modal-value">${app.message ? escapeHtml(app.message).replace(/\n/g, '<br>') : '-'}</div>
                <div class="modal-label">Форма:</div>
                <div class="modal-value">${app.form_type === 'modal' ? 'Модальная' : 'Основная'}</div>
                <div class="modal-label">Дата:</div>
                <div class="modal-value">${new Date(app.created_at).toLocaleString('ru')}</div>
                <div class="modal-label">IP адрес:</div>
                <div class="modal-value">${escapeHtml(app.ip_address) || '-'}</div>
            `;
            
            document.getElementById('detailsContent').innerHTML = details;
            document.getElementById('detailsModal').classList.add('show');
        }
        
        function escapeHtml(unsafe) {
            if (!unsafe) return unsafe;
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.remove('show');
        }
        
        // Удаление
        function deleteApplication(id) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }
        
        // Закрытие модальных окон
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(modal => {
                    modal.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>