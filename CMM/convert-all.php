<?php
/**
 * Массовая конвертация всех изображений в WebP.
 * Запускать вручную: https://voronov-art.ru/CMM/convert-all.php
 * Только для админа.
 */
session_start();

if (!isset($_SESSION['cmm_auth']) || $_SESSION['cmm_auth'] !== true) {
    die('Войдите в CMM сначала: <a href="/CMM/">Войти</a>');
}
if (($_SESSION['cmm_user_role'] ?? '') !== 'admin') {
    die('Доступ только для администратора');
}

header('Content-Type: text/html; charset=utf-8');

// Папки для обработки
$folders = [
    $_SERVER['DOCUMENT_ROOT'] . '/img/',
    $_SERVER['DOCUMENT_ROOT'] . '/img/prewiu/',
];

if (!function_exists('imagewebp')) {
    die('<pre>Ошибка: PHP не поддерживает WebP. Нужна GD с поддержкой WebP.</pre>');
}

function convertToWebp($srcPath, $quality = 85) {
    $info = @getimagesize($srcPath);
    if (!$info) return false;

    $img = null;
    switch ($info['mime']) {
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
        default:
            return false;
    }

    if (!$img) return false;

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

    return $result ? filesize($webpPath) : false;
}

function formatBytes($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 2) . ' MB';
}

$results = [];
$totalOriginal = 0;
$totalWebp = 0;
$converted = 0;
$skipped = 0;

foreach ($folders as $folder) {
    if (!is_dir($folder)) continue;

    $files = glob($folder . '*.{png,jpg,jpeg,gif,PNG,JPG,JPEG,GIF}', GLOB_BRACE);
    foreach ($files as $file) {
        $webpPath = preg_replace('/\.(png|jpe?g|gif)$/i', '.webp', $file);
        $name = basename($file);
        $relFolder = str_replace($_SERVER['DOCUMENT_ROOT'], '', $folder);

        // Пропускаем если webp уже существует и новее исходника
        if (file_exists($webpPath) && filemtime($webpPath) >= filemtime($file)) {
            $skipped++;
            $results[] = [
                'folder' => $relFolder,
                'name' => $name,
                'status' => 'skip',
                'original' => filesize($file),
                'webp' => filesize($webpPath),
            ];
            continue;
        }

        $originalSize = filesize($file);
        $webpSize = convertToWebp($file, 85);

        if ($webpSize) {
            $converted++;
            $totalOriginal += $originalSize;
            $totalWebp += $webpSize;
            $results[] = [
                'folder' => $relFolder,
                'name' => $name,
                'status' => 'ok',
                'original' => $originalSize,
                'webp' => $webpSize,
            ];
        } else {
            $results[] = [
                'folder' => $relFolder,
                'name' => $name,
                'status' => 'fail',
                'original' => $originalSize,
                'webp' => 0,
            ];
        }
    }
}

$savedPercent = $totalOriginal > 0 ? round((1 - $totalWebp / $totalOriginal) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>WebP конвертер — CMM</title>
    <style>
        body { font-family: -apple-system, sans-serif; background: #0f0f23; color: #e8e8f0; padding: 40px 20px; max-width: 900px; margin: 0 auto; }
        h1 { font-size: 24px; margin-bottom: 8px; }
        .subtitle { color: #8888aa; margin-bottom: 24px; font-size: 14px; }
        .stats { background: #1a1a35; border: 1px solid #2d2d5e; border-radius: 12px; padding: 20px; margin-bottom: 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; }
        .stat { text-align: center; }
        .stat-num { font-size: 28px; font-weight: 700; color: #6c63ff; }
        .stat-label { font-size: 12px; color: #8888aa; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; background: #1a1a35; border-radius: 12px; overflow: hidden; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #2d2d5e; font-size: 13px; }
        th { background: #252547; font-weight: 600; color: #8888aa; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        tr:last-child td { border-bottom: none; }
        .status-ok { color: #2ecc71; font-weight: 600; }
        .status-skip { color: #8888aa; }
        .status-fail { color: #e74c3c; font-weight: 600; }
        .saving { color: #2ecc71; font-weight: 600; }
        .back { display: inline-block; margin-top: 24px; color: #6c63ff; text-decoration: none; font-size: 14px; }
        .back:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>🚀 WebP конвертер</h1>
    <p class="subtitle">Массовая конвертация всех изображений в /img/ и /img/prewiu/</p>

    <div class="stats">
        <div class="stat">
            <div class="stat-num"><?= $converted ?></div>
            <div class="stat-label">Конвертировано</div>
        </div>
        <div class="stat">
            <div class="stat-num"><?= $skipped ?></div>
            <div class="stat-label">Пропущено</div>
        </div>
        <div class="stat">
            <div class="stat-num"><?= formatBytes($totalOriginal) ?></div>
            <div class="stat-label">Было</div>
        </div>
        <div class="stat">
            <div class="stat-num"><?= formatBytes($totalWebp) ?></div>
            <div class="stat-label">Стало</div>
        </div>
        <div class="stat">
            <div class="stat-num saving">−<?= $savedPercent ?>%</div>
            <div class="stat-label">Экономия</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Папка</th>
                <th>Файл</th>
                <th>Оригинал</th>
                <th>WebP</th>
                <th>Статус</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['folder']) ?></td>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><?= formatBytes($r['original']) ?></td>
                <td><?= $r['webp'] ? formatBytes($r['webp']) : '—' ?></td>
                <td>
                    <?php if ($r['status'] === 'ok'): ?>
                        <span class="status-ok">✓ конвертировано</span>
                    <?php elseif ($r['status'] === 'skip'): ?>
                        <span class="status-skip">○ уже есть</span>
                    <?php else: ?>
                        <span class="status-fail">✗ ошибка</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="/CMM/" class="back">← Вернуться в CMM</a>
</body>
</html>
