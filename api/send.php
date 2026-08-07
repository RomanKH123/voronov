<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
enforceSameOrigin();
enforceRateLimit('legacy-lead');

$name = cleanText($_POST['name'] ?? '', 100);
$phone = cleanText($_POST['phone'] ?? '', 30);
$email = cleanText($_POST['email'] ?? '', 254);
$message = cleanText($_POST['message'] ?? '', 3000);

$digits = preg_replace('/\D+/', '', $phone);
if (mb_strlen($name) < 2 || strlen($digits) < 10 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
    header('Location: /?error=1', true, 303);
    exit;
}

try {
    $stmt = db()->prepare(
        "INSERT INTO applications (name, phone, email, message, form_type, ip_address, user_agent, page_url)
         VALUES (?, ?, ?, ?, 'main', ?, ?, ?)"
    );
    $stmt->execute([
        $name,
        $phone,
        $email,
        $message,
        cleanText($_SERVER['REMOTE_ADDR'] ?? '', 45),
        cleanText($_SERVER['HTTP_USER_AGENT'] ?? '', 500),
        cleanText($_SERVER['HTTP_REFERER'] ?? '', 500),
    ]);
    header('Location: /?success=1', true, 303);
} catch (Throwable $e) {
    error_log('Lead storage failed: ' . $e->getMessage());
    header('Location: /?error=2', true, 303);
}
