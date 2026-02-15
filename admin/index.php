<?php
// admin.php - просмотр заявок
$db_host = 'localhost';
$db_name = 'voronov_studio';
$db_user = 'root';
$db_pass = '';

// Простая защита паролем
$admin_password = 'admin123'; // СМЕНИТЕ ПАРОЛЬ!

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_PW'] != $admin_password) {
    header('WWW-Authenticate: Basic realm="Admin Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Требуется авторизация';
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass
    );
    
    // Получаем заявки
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY created_at DESC");
    $applications = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die('Ошибка БД: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявки с сайта</title>
    <style>
        * { font-family: 'Inter', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(to right, white, #cce1fa); padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { margin-bottom: 30px; color: #000; font-family: 'Philosopher', sans-serif; }
        table { width: 100%; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        th { background: #000; color: white; padding: 15px; text-align: left; font-family: 'Aboreto', sans-serif; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f9f9f9; }
        .status-new { background: #ffeb3b; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .stats { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; display: flex; gap: 20px; }
        .stat-value { font-size: 24px; font-weight: bold; color: #4a6fa5; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Заявки с сайта</h1>
        
        <div class="stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo count($applications); ?></div>
                <div>Всего заявок</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">
                    <?php 
                    $new = array_filter($applications, fn($a) => $a['status'] == 'new');
                    echo count($new);
                    ?>
                </div>
                <div>Новых</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Телефон</th>
                    <th>Дата</th>
                    <th>Страница</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                <tr>
                    <td>#<?php echo $app['id']; ?></td>
                    <td><?php echo htmlspecialchars($app['name']); ?></td>
                    <td><?php echo htmlspecialchars($app['phone']); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?></td>
                    <td><a href="<?php echo htmlspecialchars($app['page_url']); ?>" target="_blank">🔗</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>