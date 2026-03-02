<?php
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
$email = isset($input['email']) ? trim($input['email']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';
$form = isset($input['form']) ? trim($input['form']) : 'main';
$page = isset($input['page']) ? trim($input['page']) : '';

// Получаем IP и User-Agent
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

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

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Введите корректный email';
}

// Если есть ошибки - возвращаем
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ===== НАСТРОЙКИ БАЗЫ ДАННЫХ =====
$db_host = 'localhost';
$db_name = 'vh384894_voronov';
$db_user = 'vh384894_voronov';
$db_pass = 'voronov20032003';

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
    
    // Вставляем данные (без проверки структуры - она уже правильная)
    $sql = "INSERT INTO applications (name, phone, email, message, form_type, page_url, ip_address, user_agent, created_at, status) 
            VALUES (:name, :phone, :email, :message, :form_type, :page, :ip, :ua, NOW(), 'new')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':phone' => $phone,
        ':email' => $email,
        ':message' => $message,
        ':form_type' => $form,
        ':page' => $page,
        ':ip' => $ip_address,
        ':ua' => $user_agent
    ]);
    
    $applicationId = $pdo->lastInsertId();
    
    // Успешный ответ
    echo json_encode([
        'success' => true,
        'message' => 'Спасибо! Ваша заявка принята.',
        'application_id' => $applicationId
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