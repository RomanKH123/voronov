<?php
session_start();

$db_host = 'localhost';
$db_name = 'vh384894_voronov';
$db_user = 'vh384894_voronov';
$db_pass = 'voronov20032003';

$pdo = null;
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
} catch (PDOException $e) {
    die('Ошибка подключения к БД');
}

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /CMM/');
    exit;
}

// Автоматическое создание таблицы cmm_users если её нет
try {
    $pdo->query("SELECT 1 FROM cmm_users LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("
        CREATE TABLE cmm_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            login VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(255) DEFAULT '',
            role ENUM('admin', 'editor') DEFAULT 'editor',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

// Проверяем, что админ существует с валидным хешем
$adminCheck = $pdo->prepare("SELECT id, password_hash FROM cmm_users WHERE login = 'admin' LIMIT 1");
$adminCheck->execute();
$admin = $adminCheck->fetch();
$validHash = password_hash('093093093Rk', PASSWORD_BCRYPT);

if (!$admin) {
    // Админа нет — создаём
    $pdo->prepare("INSERT INTO cmm_users (login, password_hash, name, role) VALUES ('admin', :h, 'Администратор', 'admin')")
        ->execute([':h' => $validHash]);
} elseif (strpos($admin['password_hash'], 'PLACEHOLDER') !== false || !password_verify('093093093Rk', $admin['password_hash'])) {
    // Хеш невалидный — обновляем
    $pdo->prepare("UPDATE cmm_users SET password_hash = :h WHERE id = :id")
        ->execute([':h' => $validHash, ':id' => $admin['id']]);
}

// Обработка входа — проверка через БД с password_verify
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'], $_POST['password'])) {
    $stmt = $pdo->prepare("SELECT id, login, password_hash, name, role FROM cmm_users WHERE login = :login LIMIT 1");
    $stmt->execute([':login' => trim($_POST['login'])]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password_hash'])) {
        $_SESSION['cmm_auth'] = true;
        $_SESSION['cmm_user_id'] = $user['id'];
        $_SESSION['cmm_user_login'] = $user['login'];
        $_SESSION['cmm_user_name'] = $user['name'];
        $_SESSION['cmm_user_role'] = $user['role'];
        header('Location: /CMM/');
        exit;
    } else {
        $login_error = 'Неверный логин или пароль';
    }
}

$is_auth = isset($_SESSION['cmm_auth']) && $_SESSION['cmm_auth'] === true;
$current_user_role = isset($_SESSION['cmm_user_role']) ? $_SESSION['cmm_user_role'] : '';
$current_user_name = isset($_SESSION['cmm_user_name']) ? $_SESSION['cmm_user_name'] : '';
$current_page = isset($_GET['page']) ? $_GET['page'] : 'works';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMM — Управление работами</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg: #0f0f23;
            --surface: #1a1a35;
            --surface-2: #252547;
            --border: #2d2d5e;
            --primary: #6c63ff;
            --primary-hover: #5a52d5;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --text: #e8e8f0;
            --text-muted: #8888aa;
            --radius: 16px;
            --radius-sm: 10px;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ===== LOGIN ===== */
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(ellipse at top, #1a1a40, #0f0f23);
        }

        .login-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 48px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }

        .login-card h1 {
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #6c63ff, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-card .subtitle {
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 32px;
        }

        .login-error {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: var(--danger);
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* ===== FORM ELEMENTS ===== */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text);
            font-size: 15px;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.15);
        }

        textarea.form-input {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.3);
        }

        .btn-success {
            background: var(--success);
            color: #fff;
        }

        .btn-success:hover {
            background: #27ae60;
        }

        .btn-danger {
            background: transparent;
            color: var(--danger);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .btn-danger:hover {
            background: rgba(231, 76, 60, 0.1);
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border);
        }

        .btn-ghost:hover {
            background: var(--surface-2);
            color: var(--text);
        }

        .btn-full {
            width: 100%;
            padding: 14px;
            font-size: 15px;
        }

        /* ===== LAYOUT ===== */
        .app {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 24px 0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
        }

        .sidebar-logo {
            padding: 0 24px 24px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .sidebar-logo h2 {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(135deg, #6c63ff, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-logo span {
            font-size: 12px;
            color: var(--text-muted);
        }

        .sidebar-nav {
            flex: 1;
            padding: 0 12px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            margin-bottom: 4px;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background: var(--surface-2);
            color: var(--text);
        }

        .sidebar-link.active {
            color: var(--primary);
        }

        .sidebar-link svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
        }

        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 32px 40px;
        }

        /* ===== HEADER ===== */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
        }

        .page-header .count {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* ===== TABLE ===== */
        .works-table {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .table-row {
            display: grid;
            grid-template-columns: 80px 1fr 160px 140px 140px;
            align-items: center;
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .table-row:hover {
            background: var(--surface-2);
        }

        .table-head {
            background: var(--surface-2);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            padding: 12px 24px;
        }

        .table-head:hover {
            background: var(--surface-2);
        }

        .table-img {
            width: 60px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--surface-2);
        }

        .table-img-placeholder {
            width: 60px;
            height: 40px;
            border-radius: 8px;
            background: var(--surface-2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 18px;
        }

        .table-title {
            font-weight: 600;
            font-size: 15px;
        }

        .table-desc {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
        }

        .table-badge {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(108, 99, 255, 0.12);
            color: var(--primary);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .table-actions {
            display: flex;
            gap: 8px;
        }

        .table-actions button {
            width: 36px;
            height: 36px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .table-actions button:hover {
            background: var(--surface-2);
            color: var(--text);
        }

        .table-actions button.delete-btn:hover {
            background: rgba(231, 76, 60, 0.1);
            border-color: rgba(231, 76, 60, 0.3);
            color: var(--danger);
        }

        /* ===== MODAL ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.open {
            display: flex;
        }

        .modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            width: 100%;
            max-width: 640px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 32px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
            animation: modalIn 0.25s ease;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: translateY(20px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 24px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 24px;
        }

        /* ===== TOAST ===== */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 14px 24px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 500;
            z-index: 300;
            animation: toastIn 0.3s ease, toastOut 0.3s ease 2.7s forwards;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .toast-success {
            background: var(--success);
            color: #fff;
        }

        .toast-error {
            background: var(--danger);
            color: #fff;
        }

        @keyframes toastIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes toastOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        /* ===== MOBILE MENU TOGGLE ===== */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 150;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            cursor: pointer;
            align-items: center;
            justify-content: center;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
            z-index: 99;
        }

        .sidebar-backdrop.open {
            display: block;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1200px) {
            .sidebar {
                width: 230px;
            }
            .main-content {
                margin-left: 230px;
                padding: 28px 28px;
            }
            .table-row {
                grid-template-columns: 70px 1fr 140px 130px 130px;
                padding: 14px 20px;
                font-size: 14px;
            }
        }

        @media (max-width: 1024px) {
            .table-row {
                grid-template-columns: 60px 1fr 120px 130px;
                font-size: 13px;
                gap: 10px;
            }
            .col-url {
                display: none;
            }
            .modal {
                max-width: 560px;
            }
        }

        @media (max-width: 860px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
                box-shadow: 4px 0 30px rgba(0, 0, 0, 0.4);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 70px 20px 24px;
            }
            .mobile-menu-btn {
                display: flex;
            }
            .page-header h1 {
                font-size: 24px;
            }
        }

        @media (max-width: 640px) {
            .main-content {
                padding: 70px 14px 20px;
            }
            .table-row {
                grid-template-columns: 56px 1fr 110px;
                padding: 12px 14px;
                gap: 10px;
            }
            .col-cat {
                display: none;
            }
            .table-img, .table-img-placeholder {
                width: 50px;
                height: 36px;
            }
            .table-title {
                font-size: 14px;
            }
            .table-desc {
                font-size: 11px;
                max-width: 180px;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .modal {
                margin: 0;
                padding: 24px 20px;
                max-height: 100vh;
                border-radius: 0;
            }
            .modal-overlay {
                padding: 0;
            }
            .modal-title {
                font-size: 20px;
                margin-bottom: 20px;
            }
            .modal-actions {
                flex-direction: column-reverse;
            }
            .modal-actions .btn {
                width: 100%;
            }
            .page-header {
                flex-direction: column;
                align-items: stretch;
                gap: 14px;
            }
            .page-header .btn {
                width: 100%;
            }
            .page-header h1 {
                font-size: 22px;
            }
            .form-input {
                padding: 12px 14px;
                font-size: 14px;
            }
            .users-table .table-row {
                grid-template-columns: 1fr 100px;
            }
            .users-table .col-id,
            .users-table .col-role,
            .users-table .col-date {
                display: none;
            }
            .users-table .table-row > div:nth-child(3) {
                display: none;
            }
            .dropzone {
                min-height: 150px;
            }
            .dropzone__content {
                padding: 18px;
            }
            .dropzone__content svg {
                width: 40px;
                height: 40px;
                margin-bottom: 8px;
            }
            .dropzone__text {
                font-size: 14px;
            }
            .toast {
                left: 14px;
                right: 14px;
                bottom: 14px;
                text-align: center;
            }
        }

        @media (max-width: 380px) {
            .table-row {
                grid-template-columns: 1fr auto;
            }
            .col-img {
                display: none;
            }
            .table-actions button {
                width: 32px;
                height: 32px;
            }
        }

        /* ===== DROPZONE ===== */
        .dropzone {
            position: relative;
            border: 2px dashed var(--border);
            border-radius: var(--radius-sm);
            background: var(--surface-2);
            min-height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            overflow: hidden;
        }

        .dropzone:hover {
            border-color: var(--primary);
            background: rgba(108, 99, 255, 0.05);
        }

        .dropzone--dragover {
            border-color: var(--primary);
            background: rgba(108, 99, 255, 0.1);
            transform: scale(1.01);
        }

        .dropzone__content {
            text-align: center;
            color: var(--text-muted);
            padding: 24px;
            pointer-events: none;
        }

        .dropzone__content svg {
            color: var(--primary);
            opacity: 0.6;
            margin-bottom: 12px;
        }

        .dropzone__text {
            font-size: 15px;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 6px;
        }

        .dropzone__text span {
            color: var(--primary);
            text-decoration: underline;
        }

        .dropzone__hint {
            font-size: 12px;
            color: var(--text-muted);
        }

        .dropzone__preview {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 180px;
        }

        .dropzone__preview img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: var(--radius-sm);
        }

        .dropzone__remove {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border: none;
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            transition: background 0.2s;
        }

        .dropzone__remove:hover {
            background: var(--danger);
        }

        .dropzone__loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ===== USERS TABLE ===== */
        .users-table .table-row {
            grid-template-columns: 50px 1fr 160px 140px 160px 120px;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .role-badge--admin {
            background: rgba(108, 99, 255, 0.15);
            color: var(--primary);
        }

        .role-badge--editor {
            background: rgba(46, 204, 113, 0.15);
            color: var(--success);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--surface-2);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: var(--primary);
            text-transform: uppercase;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            padding: 0 0 12px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-user-name {
            font-size: 13px;
            font-weight: 600;
        }

        .sidebar-user-role {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .password-hint {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Tabs */
        .nav-separator {
            height: 1px;
            background: var(--border);
            margin: 12px 4px;
        }

        .nav-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            padding: 8px 16px 4px;
            opacity: 0.6;
        }

        @media (max-width: 1024px) {
            .users-table .table-row {
                grid-template-columns: 50px 1fr 140px 140px;
            }
            .users-table .col-date {
                display: none;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }
    </style>
</head>
<body>
<?php if (!$is_auth): ?>
    <!-- LOGIN PAGE -->
    <div class="login-page">
        <div class="login-card">
            <h1>CMM Panel</h1>
            <p class="subtitle">Управление портфолио</p>
            <?php if (isset($login_error)): ?>
                <div class="login-error"><?= htmlspecialchars($login_error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Логин</label>
                    <input type="text" name="login" class="form-input" placeholder="Введите логин" required autofocus>
                </div>
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" class="form-input" placeholder="Введите пароль" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Войти</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- MAIN APP -->
    <button class="mobile-menu-btn" onclick="toggleSidebar()" aria-label="Меню">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="sidebar-backdrop" id="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <div class="app">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <h2>CMM</h2>
                <span>Content Manager</span>
            </div>
            <nav class="sidebar-nav">
                <a href="/CMM/" class="sidebar-link <?= $current_page === 'works' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    Работы
                </a>
                <?php if ($current_user_role === 'admin'): ?>
                <a href="/CMM/?page=users" class="sidebar-link <?= $current_page === 'users' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                    Пользователи
                </a>
                <?php endif; ?>
                <div class="nav-separator"></div>
                <div class="nav-label">Ссылки</div>
                <a href="/" class="sidebar-link" target="_blank">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
                    Открыть сайт
                </a>
                <a href="/admin/" class="sidebar-link" target="_blank">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Админ-панель
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="user-avatar"><?= mb_substr(htmlspecialchars($current_user_name ?: ($_SESSION['cmm_user_login'] ?? '?')), 0, 1, 'UTF-8') ?></div>
                    <div>
                        <div class="sidebar-user-name"><?= htmlspecialchars($current_user_name ?: ($_SESSION['cmm_user_login'] ?? '')) ?></div>
                        <div class="sidebar-user-role"><?= $current_user_role === 'admin' ? 'Администратор' : 'Редактор' ?></div>
                    </div>
                </div>
                <a href="?logout" class="btn btn-ghost" style="width: 100%;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Выйти
                </a>
            </div>
        </aside>

        <div class="main-content">
        <?php if ($current_page === 'users' && $current_user_role === 'admin'): ?>
            <!-- ===== СТРАНИЦА ПОЛЬЗОВАТЕЛЕЙ ===== -->
            <div class="page-header">
                <div>
                    <h1>Пользователи</h1>
                    <div class="count" id="users-count">Загрузка...</div>
                </div>
                <button class="btn btn-primary" onclick="openUserModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Добавить пользователя
                </button>
            </div>

            <div class="works-table users-table">
                <div class="table-row table-head">
                    <div class="col-id">ID</div>
                    <div>Логин</div>
                    <div>Имя</div>
                    <div class="col-role">Роль</div>
                    <div class="col-date">Дата создания</div>
                    <div>Действия</div>
                </div>
                <div id="users-list"></div>
            </div>
        <?php else: ?>
            <!-- ===== СТРАНИЦА РАБОТ ===== -->
            <div class="page-header">
                <div>
                    <h1>Работы</h1>
                    <div class="count" id="works-count">Загрузка...</div>
                </div>
                <button class="btn btn-primary" onclick="openModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Добавить работу
                </button>
            </div>

            <div class="works-table" id="works-table">
                <div class="table-row table-head">
                    <div class="col-img">Фото</div>
                    <div>Название</div>
                    <div class="col-cat">Категория</div>
                    <div class="col-url">Ссылка</div>
                    <div>Действия</div>
                </div>
                <div id="works-list"></div>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <!-- MODAL: РАБОТЫ -->
    <div class="modal-overlay" id="modal-overlay" onclick="if(event.target===this)closeModal()">
        <div class="modal">
            <h2 class="modal-title" id="modal-title">Добавить работу</h2>
            <form id="work-form" onsubmit="return saveWork(event)">
                <input type="hidden" id="work-id">

                <div class="form-group">
                    <label>Название *</label>
                    <input type="text" id="f-title" class="form-input" placeholder="Название проекта" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Основная категория (Kategory)</label>
                        <select id="f-kategory" class="form-input">
                            <option value="">-- Не выбрана --</option>
                            <option value="Сайты">Сайты</option>
                            <option value="Тестирование">Тестирование</option>
                            <option value="Дизайн">Дизайн</option>
                            <option value="Доработка">Доработка</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Подкатегория</label>
                        <input type="text" id="f-category" class="form-input" placeholder="Например: Фотостудия" list="categories-list">
                        <datalist id="categories-list"></datalist>
                    </div>
                </div>

                <div class="form-group">
                    <label>Slug (ЧПУ)</label>
                    <input type="text" id="f-slug" class="form-input" placeholder="auto-generated">
                </div>

                <div class="form-group">
                    <label>Краткое описание</label>
                    <textarea id="f-description" class="form-input" rows="2" placeholder="Краткое описание для карточки"></textarea>
                </div>

                <div class="form-group">
                    <label>Полное описание</label>
                    <textarea id="f-full-description" class="form-input" rows="4" placeholder="Подробное описание проекта"></textarea>
                </div>

                <div class="form-group">
                    <label>Превью изображение</label>
                    <div class="dropzone" id="dropzone">
                        <input type="file" id="f-image-file" accept="image/*" hidden>
                        <input type="hidden" id="f-image">
                        <div class="dropzone__content" id="dropzone-content">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <p class="dropzone__text">Перетащите фото сюда или <span>выберите файл</span></p>
                            <p class="dropzone__hint">JPG, PNG, WebP, GIF · до 10 МБ</p>
                        </div>
                        <div class="dropzone__preview" id="dropzone-preview" style="display:none;">
                            <img id="dropzone-img" src="" alt="">
                            <button type="button" class="dropzone__remove" onclick="removeImage(event)" title="Удалить">×</button>
                        </div>
                        <div class="dropzone__loading" id="dropzone-loading" style="display:none;">
                            <div class="spinner"></div>
                            <span>Загрузка...</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Ссылка на проект</label>
                    <input type="text" id="f-url" class="form-input" placeholder="/work/project/ или https://...">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" onclick="closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary" id="save-btn">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ПОЛЬЗОВАТЕЛИ -->
    <div class="modal-overlay" id="user-modal-overlay" onclick="if(event.target===this)closeUserModal()">
        <div class="modal">
            <h2 class="modal-title" id="user-modal-title">Добавить пользователя</h2>
            <form id="user-form" onsubmit="return saveUser(event)">
                <input type="hidden" id="u-id">

                <div class="form-row">
                    <div class="form-group">
                        <label>Логин *</label>
                        <input type="text" id="u-login" class="form-input" placeholder="Логин для входа" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Имя</label>
                        <input type="text" id="u-name" class="form-input" placeholder="Отображаемое имя">
                    </div>
                </div>

                <div class="form-group">
                    <label id="u-password-label">Пароль *</label>
                    <input type="password" id="u-password" class="form-input" placeholder="Минимум 6 символов" autocomplete="new-password">
                    <div class="password-hint" id="u-password-hint">Пароль будет зашифрован (bcrypt) перед сохранением в БД</div>
                </div>

                <div class="form-group" style="max-width: 300px;">
                    <label>Роль</label>
                    <select id="u-role" class="form-input">
                        <option value="editor">Редактор</option>
                        <option value="admin">Администратор</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" onclick="closeUserModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    var API = '/CMM/api.php';

    // Мобильное меню
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebar-backdrop').classList.toggle('open');
    }

    // Загрузка работ
    function loadWorks() {
        fetch(API + '?action=list')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;
                var list = document.getElementById('works-list');
                var count = document.getElementById('works-count');
                count.textContent = data.data.length + ' ' + pluralize(data.data.length, 'работа', 'работы', 'работ');

                if (data.data.length === 0) {
                    list.innerHTML =
                        '<div class="empty-state">' +
                            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>' +
                            '<h3>Пока нет работ</h3>' +
                            '<p>Добавьте первый проект в портфолио</p>' +
                            '<button class="btn btn-primary" onclick="openModal()">Добавить работу</button>' +
                        '</div>';
                    return;
                }

                var html = '';
                data.data.forEach(function(w) {
                    var imgHtml = w.image
                        ? '<img src="' + esc(w.image) + '" alt="" class="table-img">'
                        : '<div class="table-img-placeholder">&#128247;</div>';

                    var urlShort = w.url ? (w.url.length > 18 ? w.url.substring(0, 18) + '...' : w.url) : '&mdash;';

                    html +=
                        '<div class="table-row" data-id="' + w.id + '">' +
                            '<div class="col-img">' + imgHtml + '</div>' +
                            '<div>' +
                                '<div class="table-title">' + esc(w.title) + '</div>' +
                                '<div class="table-desc">' + esc(w.description) + '</div>' +
                            '</div>' +
                            '<div class="col-cat">' + (w.category ? '<span class="table-badge">' + esc(w.category) + '</span>' : '&mdash;') + '</div>' +
                            '<div class="col-url" title="' + esc(w.url) + '">' + urlShort + '</div>' +
                            '<div class="table-actions">' +
                                '<button onclick="editWork(' + w.id + ')" title="Редактировать">' +
                                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
                                '</button>' +
                                '<button class="delete-btn" onclick="deleteWork(' + w.id + ', \'' + esc(w.title).replace(/'/g, "\\'") + '\')" title="Удалить">' +
                                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>' +
                                '</button>' +
                            '</div>' +
                        '</div>';
                });

                list.innerHTML = html;
            });
    }

    // Загрузка категорий для datalist
    function loadCategories() {
        fetch(API + '?action=categories')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;
                var dl = document.getElementById('categories-list');
                dl.innerHTML = '';
                data.data.forEach(function(cat) {
                    var opt = document.createElement('option');
                    opt.value = cat;
                    dl.appendChild(opt);
                });
            });
    }

    // Открыть модалку (добавление)
    function openModal() {
        document.getElementById('modal-title').textContent = 'Добавить работу';
        document.getElementById('work-id').value = '';
        document.getElementById('work-form').reset();
        document.getElementById('f-kategory').value = '';
        document.getElementById('f-image').value = '';
        originalImage = '';
        pendingUpload = '';
        resetDropzone();
        document.getElementById('modal-overlay').classList.add('open');
        document.getElementById('f-title').focus();
        loadCategories();
    }

    // Закрыть модалку — если был загружен временный файл, но не сохранён в БД, удаляем
    function closeModal() {
        if (pendingUpload && pendingUpload !== originalImage) {
            deleteUploadedFile(pendingUpload);
        }
        pendingUpload = '';
        originalImage = '';
        document.getElementById('modal-overlay').classList.remove('open');
    }

    // Редактировать
    function editWork(id) {
        fetch(API + '?action=get&id=' + id)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;
                var w = data.data;
                document.getElementById('modal-title').textContent = 'Редактировать: ' + w.title;
                document.getElementById('work-id').value = w.id;
                document.getElementById('f-title').value = w.title;
                document.getElementById('f-slug').value = w.slug;
                document.getElementById('f-category').value = w.category || '';
                document.getElementById('f-description').value = w.description || '';
                document.getElementById('f-full-description').value = w.full_description || '';
                document.getElementById('f-image').value = w.image || '';
                document.getElementById('f-url').value = w.url || '';
                document.getElementById('f-kategory').value = w.Kategory || '';
                originalImage = w.image || '';
                pendingUpload = '';
                if (w.image) {
                    showDropzonePreview(w.image);
                } else {
                    resetDropzone();
                }
                document.getElementById('modal-overlay').classList.add('open');
                loadCategories();
            });
    }

    // Сохранить
    function saveWork(e) {
        e.preventDefault();
        var id = document.getElementById('work-id').value;
        var body = {
            title: document.getElementById('f-title').value,
            slug: document.getElementById('f-slug').value,
            category: document.getElementById('f-category').value,
            Kategory: document.getElementById('f-kategory').value,
            description: document.getElementById('f-description').value,
            full_description: document.getElementById('f-full-description').value,
            image: document.getElementById('f-image').value,
            url: document.getElementById('f-url').value
        };

        var method = 'POST';
        if (id) {
            method = 'PUT';
            body.id = parseInt(id);
        }

        fetch(API, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                // Сохранили — сбрасываем pending чтобы closeModal не удалил файл
                pendingUpload = '';
                originalImage = '';
                toast(id ? 'Работа обновлена' : 'Работа добавлена', 'success');
                closeModal();
                loadWorks();
            } else {
                toast(data.message || 'Ошибка', 'error');
            }
        })
        .catch(function() {
            toast('Ошибка сети', 'error');
        });

        return false;
    }

    // Удалить
    function deleteWork(id, title) {
        if (!confirm('Удалить работу "' + title + '"?')) return;

        fetch(API + '?id=' + id, { method: 'DELETE' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    toast('Работа удалена', 'success');
                    loadWorks();
                } else {
                    toast('Ошибка удаления', 'error');
                }
            });
    }

    // Toast уведомления
    function toast(message, type) {
        var el = document.createElement('div');
        el.className = 'toast toast-' + type;
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(function() { el.remove(); }, 3000);
    }

    // Склонение
    function pluralize(n, one, few, many) {
        var mod10 = n % 10, mod100 = n % 100;
        if (mod10 === 1 && mod100 !== 11) return one;
        if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return few;
        return many;
    }

    // Экранирование
    function esc(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // ===== ПОЛЬЗОВАТЕЛИ =====

    function loadUsers() {
        var list = document.getElementById('users-list');
        if (!list) return;

        fetch(API + '?action=users_list')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;
                var count = document.getElementById('users-count');
                count.textContent = data.data.length + ' ' + pluralize(data.data.length, 'пользователь', 'пользователя', 'пользователей');

                if (data.data.length === 0) {
                    list.innerHTML =
                        '<div class="empty-state">' +
                            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/></svg>' +
                            '<h3>Нет пользователей</h3>' +
                            '<p>Добавьте первого пользователя</p>' +
                            '<button class="btn btn-primary" onclick="openUserModal()">Добавить</button>' +
                        '</div>';
                    return;
                }

                var html = '';
                data.data.forEach(function(u) {
                    var initial = (u.name || u.login).charAt(0).toUpperCase();
                    var roleBadge = u.role === 'admin'
                        ? '<span class="role-badge role-badge--admin">Админ</span>'
                        : '<span class="role-badge role-badge--editor">Редактор</span>';
                    var dateStr = u.created_at ? u.created_at.substring(0, 10) : '&mdash;';

                    html +=
                        '<div class="table-row">' +
                            '<div class="col-id">' + u.id + '</div>' +
                            '<div>' +
                                '<div class="table-title">' + esc(u.login) + '</div>' +
                            '</div>' +
                            '<div>' + esc(u.name || '&mdash;') + '</div>' +
                            '<div class="col-role">' + roleBadge + '</div>' +
                            '<div class="col-date">' + dateStr + '</div>' +
                            '<div class="table-actions">' +
                                '<button onclick="editUser(' + u.id + ')" title="Редактировать">' +
                                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
                                '</button>' +
                                '<button class="delete-btn" onclick="deleteUser(' + u.id + ', \'' + esc(u.login).replace(/'/g, "\\'") + '\')" title="Удалить">' +
                                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>' +
                                '</button>' +
                            '</div>' +
                        '</div>';
                });

                list.innerHTML = html;
            });
    }

    function openUserModal() {
        document.getElementById('user-modal-title').textContent = 'Добавить пользователя';
        document.getElementById('u-id').value = '';
        document.getElementById('user-form').reset();
        document.getElementById('u-password').required = true;
        document.getElementById('u-password-label').textContent = 'Пароль *';
        document.getElementById('u-password-hint').textContent = 'Пароль будет зашифрован (bcrypt) перед сохранением в БД';
        document.getElementById('u-login').readOnly = false;
        document.getElementById('user-modal-overlay').classList.add('open');
        document.getElementById('u-login').focus();
    }

    function closeUserModal() {
        document.getElementById('user-modal-overlay').classList.remove('open');
    }

    function editUser(id) {
        fetch(API + '?action=user_get&id=' + id)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;
                var u = data.data;
                document.getElementById('user-modal-title').textContent = 'Редактировать: ' + u.login;
                document.getElementById('u-id').value = u.id;
                document.getElementById('u-login').value = u.login;
                document.getElementById('u-login').readOnly = true;
                document.getElementById('u-name').value = u.name || '';
                document.getElementById('u-role').value = u.role || 'editor';
                document.getElementById('u-password').value = '';
                document.getElementById('u-password').required = false;
                document.getElementById('u-password-label').textContent = 'Новый пароль';
                document.getElementById('u-password-hint').textContent = 'Оставьте пустым, чтобы не менять пароль';
                document.getElementById('user-modal-overlay').classList.add('open');
            });
    }

    function saveUser(e) {
        e.preventDefault();
        var id = document.getElementById('u-id').value;
        var password = document.getElementById('u-password').value;

        if (!id && password.length < 6) {
            toast('Пароль должен быть не менее 6 символов', 'error');
            return false;
        }
        if (id && password && password.length < 6) {
            toast('Пароль должен быть не менее 6 символов', 'error');
            return false;
        }

        var body = {
            login: document.getElementById('u-login').value,
            name: document.getElementById('u-name').value,
            role: document.getElementById('u-role').value
        };

        if (password) {
            body.password = password;
        }

        var method = 'POST';
        var url = API + '?action=user_create';

        if (id) {
            method = 'PUT';
            url = API + '?action=user_update';
            body.id = parseInt(id);
        }

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                toast(id ? 'Пользователь обновлён' : 'Пользователь добавлен', 'success');
                closeUserModal();
                loadUsers();
            } else {
                toast(data.message || 'Ошибка', 'error');
            }
        })
        .catch(function() {
            toast('Ошибка сети', 'error');
        });

        return false;
    }

    function deleteUser(id, login) {
        if (!confirm('Удалить пользователя "' + login + '"?')) return;

        fetch(API + '?action=user_delete&id=' + id, { method: 'DELETE' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    toast('Пользователь удалён', 'success');
                    loadUsers();
                } else {
                    toast(data.message || 'Ошибка удаления', 'error');
                }
            });
    }

    // Клавиша Escape закрывает модалки
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            closeUserModal();
        }
    });

    // ===== DROPZONE =====
    function initDropzone() {
        var dropzone = document.getElementById('dropzone');
        var fileInput = document.getElementById('f-image-file');
        if (!dropzone || !fileInput) return;

        // Клик по зоне = открыть выбор файла
        dropzone.addEventListener('click', function(e) {
            if (e.target.closest('.dropzone__remove')) return;
            fileInput.click();
        });

        // Drag & drop
        ['dragenter', 'dragover'].forEach(function(ev) {
            dropzone.addEventListener(ev, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dropzone--dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function(ev) {
            dropzone.addEventListener(ev, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dropzone--dragover');
            });
        });

        dropzone.addEventListener('drop', function(e) {
            var files = e.dataTransfer.files;
            if (files && files.length > 0) {
                uploadImage(files[0]);
            }
        });

        fileInput.addEventListener('change', function() {
            if (fileInput.files && fileInput.files[0]) {
                uploadImage(fileInput.files[0]);
            }
        });

        // Защита: отключаем drop по всему окну (чтобы файл не открывался в браузере)
        window.addEventListener('dragover', function(e) { e.preventDefault(); });
        window.addEventListener('drop', function(e) { e.preventDefault(); });
    }

    // Хранит исходное изображение работы (для отслеживания изменений)
    var originalImage = '';
    // Временно загруженный файл, который ещё не сохранён в БД
    var pendingUpload = '';

    function uploadImage(file) {
        if (!file.type.startsWith('image/')) {
            toast('Можно загружать только изображения', 'error');
            return;
        }

        document.getElementById('dropzone-content').style.display = 'none';
        document.getElementById('dropzone-preview').style.display = 'none';
        document.getElementById('dropzone-loading').style.display = 'flex';

        var formData = new FormData();
        formData.append('image', file);

        fetch('/CMM/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('dropzone-loading').style.display = 'none';
            if (data.success) {
                // Если уже был загружен временный файл — удаляем его
                if (pendingUpload && pendingUpload !== data.path) {
                    deleteUploadedFile(pendingUpload);
                }
                pendingUpload = data.path;
                document.getElementById('f-image').value = data.path;
                showDropzonePreview(data.path);
                toast('Фото загружено', 'success');
            } else {
                resetDropzone();
                toast(data.message || 'Ошибка загрузки', 'error');
            }
        })
        .catch(function() {
            document.getElementById('dropzone-loading').style.display = 'none';
            resetDropzone();
            toast('Ошибка сети', 'error');
        });
    }

    // Удаление файла с сервера (временно загруженного)
    function deleteUploadedFile(path) {
        if (!path) return;
        fetch('/CMM/upload.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ path: path })
        }).catch(function() {});
    }

    function showDropzonePreview(path) {
        document.getElementById('dropzone-content').style.display = 'none';
        document.getElementById('dropzone-loading').style.display = 'none';
        document.getElementById('dropzone-preview').style.display = 'block';
        document.getElementById('dropzone-img').src = path;
    }

    function resetDropzone() {
        document.getElementById('f-image').value = '';
        document.getElementById('f-image-file').value = '';
        document.getElementById('dropzone-preview').style.display = 'none';
        document.getElementById('dropzone-loading').style.display = 'none';
        document.getElementById('dropzone-content').style.display = 'block';
        document.getElementById('dropzone-img').src = '';
    }

    function removeImage(e) {
        e.stopPropagation();
        // Удаляем с сервера временно загруженный файл (если был)
        if (pendingUpload) {
            deleteUploadedFile(pendingUpload);
            pendingUpload = '';
        }
        resetDropzone();
    }

    // Старт
    var currentPage = '<?= $current_page ?>';
    if (currentPage === 'users') {
        loadUsers();
    } else {
        loadWorks();
        initDropzone();
    }
    </script>
<?php endif; ?>
</body>
</html>
