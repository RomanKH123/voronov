<?php
require_once __DIR__ . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

try {
    $pdo = db();

    // Если передан id — возвращаем одну работу
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM works WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $work = $stmt->fetch();

        if (!$work) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Работа не найдена']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $work]);
    } elseif (isset($_GET['kategory'])) {
        // Фильтрация по основной категории (Kategory enum)
        $kategory = cleanText($_GET['kategory'], 100);
        $stmt = $pdo->prepare("SELECT * FROM works WHERE Kategory = :kategory ORDER BY created_at DESC, id DESC");
        $stmt->execute([':kategory' => $kategory]);
        $works = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $works]);
    } elseif (isset($_GET['grouped'])) {
        // Группировка по Kategory (enum) для главной страницы
        $stmt = $pdo->query("SELECT * FROM works ORDER BY created_at DESC, id DESC");
        $works = $stmt->fetchAll();

        $grouped = [];
        foreach ($works as $work) {
            // Используем Kategory, если пустая — fallback на category
            $cat = !empty($work['Kategory']) ? $work['Kategory'] : (!empty($work['category']) ? $work['category'] : '');
            if (empty($cat)) continue;
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $work;
        }

        echo json_encode(['success' => true, 'data' => $grouped]);
    } else {
        // Возвращаем все работы
        $stmt = $pdo->query("SELECT * FROM works ORDER BY created_at DESC, id DESC");
        $works = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $works]);
    }

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка при загрузке данных']);
}
?>
