<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Проверка авторизации
if (!isset($_SESSION['cmm_auth']) || $_SESSION['cmm_auth'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

/**
 * Конвертация изображения в WebP
 * Возвращает true если успешно
 */
function convertToWebp($srcPath, $quality = 85) {
    if (!function_exists('imagewebp')) return false;

    $info = @getimagesize($srcPath);
    if (!$info) return false;

    $mime = $info['mime'];
    $img = null;

    switch ($mime) {
        case 'image/jpeg':
            $img = @imagecreatefromjpeg($srcPath);
            break;
        case 'image/png':
            $img = @imagecreatefrompng($srcPath);
            if ($img) {
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
            }
            break;
        case 'image/gif':
            $img = @imagecreatefromgif($srcPath);
            break;
        case 'image/webp':
            return true; // уже webp
    }

    if (!$img) return false;

    // Ресайз если слишком большое (макс 1600px по большей стороне)
    $w = imagesx($img);
    $h = imagesy($img);
    $maxSide = 1600;
    if ($w > $maxSide || $h > $maxSide) {
        $ratio = $w / $h;
        if ($w > $h) {
            $newW = $maxSide;
            $newH = (int)($maxSide / $ratio);
        } else {
            $newH = $maxSide;
            $newW = (int)($maxSide * $ratio);
        }
        $resized = imagecreatetruecolor($newW, $newH);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($img);
        $img = $resized;
    }

    $webpPath = preg_replace('/\.(png|jpe?g|gif)$/i', '.webp', $srcPath);
    $result = imagewebp($img, $webpPath, $quality);
    imagedestroy($img);

    return $result;
}

/**
 * Удаление файла + связанной WebP-версии
 */
function deleteFileWithWebp($fullPath) {
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
    // Удаляем парную webp
    $webpPath = preg_replace('/\.(png|jpe?g|gif)$/i', '.webp', $fullPath);
    if ($webpPath !== $fullPath && is_file($webpPath)) {
        @unlink($webpPath);
    }
    // Если удаляем сам webp — удаляем и оригинал (на всякий случай)
    if (preg_match('/\.webp$/i', $fullPath)) {
        foreach (['png', 'jpg', 'jpeg', 'gif'] as $ext) {
            $orig = preg_replace('/\.webp$/i', '.' . $ext, $fullPath);
            if (is_file($orig)) @unlink($orig);
        }
    }
}

// Удаление загруженного, но не сохранённого файла
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || (isset($_GET['action']) && $_GET['action'] === 'delete')) {
    $input = json_decode(file_get_contents('php://input'), true);
    $path = $input['path'] ?? ($_GET['path'] ?? '');

    if (empty($path) || strpos($path, '/img/prewiu/') !== 0 || strpos($path, '..') !== false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Неверный путь']);
        exit;
    }

    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
    $realPath = realpath($fullPath);
    $allowedDir = realpath($_SERVER['DOCUMENT_ROOT'] . '/img/prewiu/');

    if ($realPath && $allowedDir && strpos($realPath, $allowedDir) === 0 && is_file($realPath)) {
        deleteFileWithWebp($realPath);
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешён']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Файл не получен']);
    exit;
}

$file = $_FILES['image'];

// Проверка типа
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$mime = mime_content_type($file['tmp_name']);
if (!in_array($mime, $allowed)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Недопустимый формат. Разрешены: JPG, PNG, WebP, GIF']);
    exit;
}

// Проверка размера (макс 10 МБ)
if ($file['size'] > 10 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Файл слишком большой (макс 10 МБ)']);
    exit;
}

// Целевая папка
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/prewiu/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Расширение
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
    $ext = 'png';
}

// Уникальное имя на основе оригинального
$baseName = preg_replace('/[^a-z0-9_-]/i', '_', pathinfo($file['name'], PATHINFO_FILENAME));
$baseName = strtolower(substr($baseName, 0, 50));
if (empty($baseName)) {
    $baseName = 'image';
}

$fileName = $baseName . '.' . $ext;
$filePath = $uploadDir . $fileName;
$counter = 1;
while (file_exists($filePath)) {
    $fileName = $baseName . '_' . $counter . '.' . $ext;
    $filePath = $uploadDir . $fileName;
    $counter++;
}

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Не удалось сохранить файл']);
    exit;
}

// Автоматическая конвертация в WebP
$webpCreated = convertToWebp($filePath, 85);

echo json_encode([
    'success' => true,
    'path' => '/img/prewiu/' . $fileName,
    'name' => $fileName,
    'webp' => $webpCreated
]);
?>
