<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Проверка авторизации
if (!isset($_SESSION['cmm_auth']) || $_SESSION['cmm_auth'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$db_host = 'localhost';
$db_name = 'vh384894_voronov';
$db_user = 'vh384894_voronov';
$db_pass = 'voronov20032003';

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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к БД']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Проверка роли админа для операций с пользователями
function requireAdmin() {
    if (!isset($_SESSION['cmm_user_role']) || $_SESSION['cmm_user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав']);
        exit;
    }
}

// Безопасное удаление файла превью с сервера (включая WebP-версию)
function deletePreviewFile($path) {
    if (empty($path)) return;
    // Удаляем только файлы из /img/prewiu/ — защита от path traversal
    if (strpos($path, '/img/prewiu/') !== 0) return;
    if (strpos($path, '..') !== false) return;

    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
    $realPath = realpath($fullPath);
    $allowedDir = realpath($_SERVER['DOCUMENT_ROOT'] . '/img/prewiu/');

    if ($realPath && $allowedDir && strpos($realPath, $allowedDir) === 0 && is_file($realPath)) {
        @unlink($realPath);
        // Удаляем парную WebP-версию
        $webpPath = preg_replace('/\.(png|jpe?g|gif)$/i', '.webp', $realPath);
        if ($webpPath !== $realPath && is_file($webpPath)) {
            @unlink($webpPath);
        }
    }
}

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            $stmt = $pdo->query("SELECT * FROM works ORDER BY sort_order ASC, created_at DESC");
            $works = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $works]);
        } elseif ($action === 'get' && isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("SELECT * FROM works WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $work = $stmt->fetch();
            if ($work) {
                echo json_encode(['success' => true, 'data' => $work]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Не найдено']);
            }
        } elseif ($action === 'categories') {
            $stmt = $pdo->query("SELECT DISTINCT category FROM works WHERE category != '' ORDER BY category");
            $cats = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo json_encode(['success' => true, 'data' => $cats]);

        // ----- USERS -----
        } elseif ($action === 'users_list') {
            requireAdmin();
            $stmt = $pdo->query("SELECT id, login, name, role, created_at FROM cmm_users ORDER BY id ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } elseif ($action === 'user_get' && isset($_GET['id'])) {
            requireAdmin();
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("SELECT id, login, name, role, created_at FROM cmm_users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch();
            if ($user) {
                echo json_encode(['success' => true, 'data' => $user]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Неверный запрос']);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Нет данных']);
            break;
        }

        // --- Создание пользователя ---
        if ($action === 'user_create') {
            requireAdmin();
            $login = trim($input['login'] ?? '');
            $password = $input['password'] ?? '';
            $name = trim($input['name'] ?? '');
            $role = in_array($input['role'] ?? '', ['admin', 'editor']) ? $input['role'] : 'editor';

            if ($login === '' || mb_strlen($password) < 6) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Логин обязателен, пароль минимум 6 символов']);
                break;
            }

            // Проверяем уникальность логина
            $check = $pdo->prepare("SELECT id FROM cmm_users WHERE login = :login");
            $check->execute([':login' => $login]);
            if ($check->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует']);
                break;
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO cmm_users (login, password_hash, name, role) VALUES (:login, :hash, :name, :role)");
            $stmt->execute([
                ':login' => $login,
                ':hash' => $hash,
                ':name' => $name,
                ':role' => $role
            ]);

            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;
        }

        // --- Создание работы ---
        $title = trim($input['title'] ?? '');
        $slug = trim($input['slug'] ?? '');
        $category = trim($input['category'] ?? '');
        $kategory = trim($input['Kategory'] ?? '');
        $description = trim($input['description'] ?? '');
        $full_description = trim($input['full_description'] ?? '');
        $image = trim($input['image'] ?? '');
        $url = trim($input['url'] ?? '');

        if ($title === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Название обязательно']);
            break;
        }

        if ($slug === '') {
            $slug = transliterate($title);
        }

        // Автонумерация: max + 1
        $maxStmt = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM works");
        $sort_order = (int)$maxStmt->fetchColumn();

        $stmt = $pdo->prepare("INSERT INTO works (title, slug, category, Kategory, description, full_description, image, url, sort_order) VALUES (:title, :slug, :category, :kategory, :description, :full_description, :image, :url, :sort_order)");
        $stmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':category' => $category,
            ':kategory' => $kategory,
            ':description' => $description,
            ':full_description' => $full_description,
            ':image' => $image,
            ':url' => $url,
            ':sort_order' => $sort_order
        ]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Нет данных или ID']);
            break;
        }

        // --- Обновление пользователя ---
        if ($action === 'user_update') {
            requireAdmin();
            $id = (int)$input['id'];
            $name = trim($input['name'] ?? '');
            $role = in_array($input['role'] ?? '', ['admin', 'editor']) ? $input['role'] : 'editor';

            // Нельзя убрать админа у самого себя
            if ($id === (int)($_SESSION['cmm_user_id'] ?? 0) && $role !== 'admin') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Нельзя убрать роль админа у самого себя']);
                break;
            }

            if (!empty($input['password'])) {
                if (mb_strlen($input['password']) < 6) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Пароль минимум 6 символов']);
                    break;
                }
                $hash = password_hash($input['password'], PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE cmm_users SET name = :name, role = :role, password_hash = :hash WHERE id = :id");
                $stmt->execute([':name' => $name, ':role' => $role, ':hash' => $hash, ':id' => $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE cmm_users SET name = :name, role = :role WHERE id = :id");
                $stmt->execute([':name' => $name, ':role' => $role, ':id' => $id]);
            }

            echo json_encode(['success' => true]);
            break;
        }

        // --- Обновление работы ---
        $id = (int)$input['id'];
        $title = trim($input['title'] ?? '');
        $slug = trim($input['slug'] ?? '');
        $category = trim($input['category'] ?? '');
        $kategory = trim($input['Kategory'] ?? '');
        $description = trim($input['description'] ?? '');
        $full_description = trim($input['full_description'] ?? '');
        $image = trim($input['image'] ?? '');
        $url = trim($input['url'] ?? '');

        if ($title === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Название обязательно']);
            break;
        }

        // Получаем старое изображение и удаляем если изменилось
        $oldStmt = $pdo->prepare("SELECT image FROM works WHERE id = :id");
        $oldStmt->execute([':id' => $id]);
        $oldImage = $oldStmt->fetchColumn();
        if ($oldImage && $oldImage !== $image) {
            deletePreviewFile($oldImage);
        }

        $stmt = $pdo->prepare("UPDATE works SET title = :title, slug = :slug, category = :category, Kategory = :kategory, description = :description, full_description = :full_description, image = :image, url = :url WHERE id = :id");
        $stmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':category' => $category,
            ':kategory' => $kategory,
            ':description' => $description,
            ':full_description' => $full_description,
            ':image' => $image,
            ':url' => $url,
            ':id' => $id
        ]);

        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        // --- Удаление пользователя ---
        if ($action === 'user_delete') {
            requireAdmin();
            $id = (int)$_GET['id'];

            // Нельзя удалить самого себя
            if ($id === (int)($_SESSION['cmm_user_id'] ?? 0)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Нельзя удалить самого себя']);
                break;
            }

            $stmt = $pdo->prepare("DELETE FROM cmm_users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
            break;
        }

        // --- Удаление работы ---
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID не указан']);
            break;
        }

        $id = (int)$_GET['id'];

        // Удаляем файл превью
        $oldStmt = $pdo->prepare("SELECT image FROM works WHERE id = :id");
        $oldStmt->execute([':id' => $id]);
        $oldImage = $oldStmt->fetchColumn();
        if ($oldImage) {
            deletePreviewFile($oldImage);
        }

        $stmt = $pdo->prepare("DELETE FROM works WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
}

function transliterate($str) {
    $map = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo',
        'ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m',
        'н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u',
        'ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch',
        'ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
        ' '=>'-'
    ];
    $str = mb_strtolower($str, 'UTF-8');
    $str = strtr($str, $map);
    $str = preg_replace('/[^a-z0-9\-]/', '', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}
?>
