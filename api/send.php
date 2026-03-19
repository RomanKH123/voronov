<?php
// api/send.php - принимает заявки и сохраняет в БД

$db_host = 'localhost';
$db_name = 'vh384894_voronov';
$db_user = 'vh384894_voronov';
$db_pass = 'voronov20032003';


// Получаем данные из формы
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$message = $_POST['message'] ?? '';

// Валидация
if (empty($name) || empty($phone)) {
    header('Location: /?error=1');
    exit;
}

// Сохраняем в БД (используя ваше подключение)
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=vh384894_voronov;charset=utf8mb4",
        'vh384894_voronov',
        'voronov20032003'
    );
    
    $stmt = $pdo->prepare("
        INSERT INTO applications (name, phone, email, message, form_type, ip_address, user_agent, page_url) 
        VALUES (?, ?, ?, ?, 'main', ?, ?, ?)
    ");
    
    $stmt->execute([
        $name,
        $phone,
        $email,
        $message,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'],
        $_SERVER['HTTP_REFERER'] ?? ''
    ]);
    
    header('Location: /?success=1');
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: /?error=2');
}