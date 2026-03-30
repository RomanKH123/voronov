<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
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
    } else {
        // Возвращаем все работы
        $stmt = $pdo->query("SELECT * FROM works ORDER BY sort_order ASC, created_at DESC");
        $works = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $works]);
    }

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка при загрузке данных']);
}
?>