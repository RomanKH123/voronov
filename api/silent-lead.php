<?php
/**
 * silent-lead.php
 * API для приема тихих заявок
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ========== КОНФИГУРАЦИЯ ==========
$db_host = 'localhost';
$db_name = 'vh384894_voronov';
$db_user = 'vh384894_voronov';
$db_pass = 'voronov20032003';

// ТВОЙ КОНТАКТНЫЙ НОМЕР (только цифры)
$STUDIO_PHONE_DIGITS = '79604987864';

// ========== ПОЛУЧЕНИЕ ДАННЫХ ==========
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data && $_POST) {
    $data = $_POST;
}

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'No data received']);
    exit;
}

// ========== ОЧИСТКА ТЕЛЕФОНА ==========
function cleanPhone($phone) {
    if (empty($phone)) return '';
    return preg_replace('/\D/', '', $phone);
}

// ========== ИЗВЛЕЧЕНИЕ ТЕЛЕФОНА ==========
$userPhone = '';

if (!empty($data['phone'])) {
    $userPhone = cleanPhone($data['phone']);
}

// ========== ПРОВЕРКА ==========
if (empty($userPhone) || strlen($userPhone) < 10) {
    http_response_code(200);
    echo json_encode(['status' => 'skipped', 'reason' => 'no_phone']);
    exit;
}

// Исключаем только твой номер
if ($userPhone === $STUDIO_PHONE_DIGITS) {
    http_response_code(200);
    echo json_encode(['status' => 'skipped', 'reason' => 'studio_phone']);
    exit;
}

// ========== ОСТАЛЬНЫЕ ДАННЫЕ ==========
$userName = !empty($data['name']) ? trim($data['name']) : 'Аноним (cookie)';
$userEmail = !empty($data['email']) ? trim($data['email']) : '';

// Форматируем телефон для вывода
$formattedPhone = '+7' . substr($userPhone, -10);
if (strlen($userPhone) === 10) {
    $formattedPhone = '+7' . $userPhone;
} elseif (strlen($userPhone) === 11 && substr($userPhone, 0, 1) === '8') {
    $formattedPhone = '+7' . substr($userPhone, 1);
} elseif (strlen($userPhone) === 11 && substr($userPhone, 0, 2) === '79') {
    $formattedPhone = '+' . $userPhone;
}

$description = "📊 ЗАЯВКА ОТ COOKIE СОГЛАСИЯ\n";
$description .= "===================================\n\n";
$description .= "👤 Имя: $userName\n";
$description .= "📞 Телефон: $formattedPhone\n";
if ($userEmail) $description .= "✉️ Email: $userEmail\n";
$description .= "\n🕐 Время: " . date('d.m.Y H:i:s') . "\n";
$description .= "🔗 Страница: " . ($data['page'] ?? 'Неизвестно') . "\n";
$description .= "🔙 Referrer: " . ($data['referrer'] ?? 'Прямой заход') . "\n";
$description .= "🌐 IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Неизвестно') . "\n";
$description .= "📱 Браузер: " . ($data['user_agent'] ?? 'Неизвестно');

// ========== СОХРАНЕНИЕ ==========
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $pdo->prepare("
        INSERT INTO applications (name, phone, email, message, form_type, ip_address, user_agent, status, created_at)
        VALUES (?, ?, ?, ?, 'cookie_consent', ?, ?, 'new', NOW())
    ");
    
    $stmt->execute([
        $userName,
        $formattedPhone,
        $userEmail,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'id' => $pdo->lastInsertId()]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}