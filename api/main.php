<?php
require_once __DIR__ . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

// Только POST запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Метод не разрешен'], 405);
}
enforceSameOrigin();
enforceRateLimit('lead');

if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) {
    jsonResponse(['success' => false, 'message' => 'Слишком большой запрос'], 413);
}

// Получаем данные из POST (как JSON)
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Неверный формат данных']);
    exit;
}

// Извлекаем данные
$name = cleanText($input['name'] ?? '', 100);
$phone = cleanText($input['phone'] ?? '', 30);
$email = cleanText($input['email'] ?? '', 254);
$message = cleanText($input['message'] ?? '', 3000);
$form = cleanText($input['form'] ?? 'main', 50);
$page = cleanText($input['page'] ?? '', 500);
$consent = ($input['consent'] ?? false) === true;

// Получаем IP и User-Agent
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Валидация
$errors = [];

if (!$consent) {
    $errors['consent'] = 'Необходимо согласие на обработку персональных данных';
}

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

try {
    $pdo = db();
    
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
