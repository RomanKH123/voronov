<?php
// send_application.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Только POST запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

// Получаем данные из POST (как JSON)
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Неверный формат данных']);
    exit;
}

// Извлекаем данные
$name = isset($input['name']) ? trim($input['name']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$page = isset($input['page']) ? trim($input['page']) : '';

// Валидация
$errors = [];

if (empty($name)) {
    $errors['name'] = 'Имя обязательно';
} elseif (mb_strlen($name, 'utf-8') < 2) {
    $errors['name'] = 'Имя должно содержать минимум 2 символа';
}

if (empty($phone)) {
    $errors['phone'] = 'Телефон обязателен';
} else {
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleanPhone) < 10) {
        $errors['phone'] = 'Введите корректный номер телефона';
    }
}

// Если есть ошибки - возвращаем
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ===== НАСТРОЙКИ БАЗЫ ДАННЫХ =====
// ЗАМЕНИТЕ НА СВОИ ЗНАЧЕНИЯ!
$db_host = 'localhost';      // обычно localhost
$db_name = 'your_database';  // название вашей базы данных
$db_user = 'your_username';  // пользователь (обычно root на локальном сервере)
$db_pass = 'your_password';  // пароль (на локальном сервере часто пустой)

try {
    // Подключение к базе данных
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Создаем таблицу, если её нет
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            page_url TEXT,
            created_at DATETIME NOT NULL,
            status ENUM('new', 'processed', 'completed') DEFAULT 'new',
            INDEX (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Вставляем данные
    $sql = "INSERT INTO applications (name, phone, page_url, created_at, status) 
            VALUES (:name, :phone, :page, NOW(), 'new')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':phone' => $phone,
        ':page' => $page
    ]);
    
    // Успешный ответ
    echo json_encode([
        'success' => true,
        'message' => 'Спасибо! Ваша заявка принята.',
        'application_id' => $pdo->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    // Логируем ошибку
    error_log('Database error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при сохранении заявки. Пожалуйста, попробуйте позже.'
    ]);
}
?>