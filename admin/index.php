<?php
// index.php - просмотр и управление заявками
$db_host = 'localhost';
$db_name = 'vh384894_voronov';
$db_user = 'vh384894_voronov';
$db_pass = 'voronov20032003';

// Простая защита паролем
$admin_password = '227227Vst';

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_PW'] != $admin_password) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Требуется авторизация';
    exit;
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $pdo = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
            $db_user,
            $db_pass
        );
        
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            header('Location: index.php?deleted=1');
            exit;
        }
        
        if ($_POST['action'] === 'update_status' && isset($_POST['id']) && isset($_POST['status'])) {
            $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
            $stmt->execute([$_POST['status'], $_POST['id']]);
            header('Location: index.php?updated=1');
            exit;
        }
    } catch (PDOException $e) {
        $error = 'Ошибка БД: ' . $e->getMessage();
    }
}

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass
    );
    
    // Получаем заявки
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY 
        CASE status 
            WHEN 'new' THEN 1 
            WHEN 'processed' THEN 2 
            ELSE 3 
        END, created_at DESC");
    $applications = $stmt->fetchAll();
    
    // Статистика
    $total = count($applications);
    $new = array_filter($applications, fn($a) => $a['status'] == 'new');
    $processed = array_filter($applications, fn($a) => $a['status'] == 'processed');
    $completed = array_filter($applications, fn($a) => $a['status'] == 'completed');
    
    // За сегодня
    $today = array_filter($applications, fn($a) => date('Y-m-d', strtotime($a['created_at'])) == date('Y-m-d'));
    
} catch (PDOException $e) {
    die('Ошибка БД: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Заявки с сайта</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Шапка */
        .header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        h1 {
            font-size: 32px;
            color: #000;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        h1 span {
            background: #4a6fa5;
            color: white;
            font-size: 14px;
            padding: 5px 15px;
            border-radius: 30px;
            font-weight: normal;
        }
        
        /* Статистика */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #4a6fa5;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        /* Уведомления */
        .notification {
            background: #4CAF50;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            animation: slideDown 0.5s;
        }
        
        .notification.error {
            background: #dc3545;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Фильтры */
        .filters {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: #4a6fa5;
            border-color: #4a6fa5;
            color: white;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .search-box::before {
            content: '🔍';
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        /* Таблица */
        .table-container {
            background: white;
            border-radius: 20px;
            overflow-x: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }
        
        th {
            background: #000;
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            vertical-align: middle;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        /* Строки с разными статусами */
        tr.status-new {
            background-color: #fff9e6;
        }
        
        tr.status-new:hover {
            background-color: #fff3d4;
        }
        
        tr.status-processed {
            background-color: #e6f3ff;
        }
        
        tr.status-processed:hover {
            background-color: #d4e8ff;
        }
        
        tr.status-completed {
            background-color: #e6ffe6;
        }
        
        tr.status-completed:hover {
            background-color: #d4ffd4;
        }
        
        /* Ячейки с контентом */
        .id-cell {
            font-weight: bold;
            color: #4a6fa5;
        }
        
        .date-cell {
            white-space: nowrap;
            font-size: 13px;
            color: #666;
        }
        
        .name-cell {
            font-weight: 500;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .phone-cell a {
            color: #4a6fa5;
            text-decoration: none;
            font-weight: 500;
        }
        
        .phone-cell a:hover {
            text-decoration: underline;
        }
        
        .email-cell {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .email-cell a {
            color: #4a6fa5;
            text-decoration: none;
        }
        
        .email-cell a:hover {
            text-decoration: underline;
        }
        
        .message-cell {
            max-width: 200px;
            cursor: pointer;
            color: #666;
        }
        
        .message-preview {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .message-preview:hover {
            color: #4a6fa5;
        }
        
        .form-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .form-type.modal {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .form-type.main {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .ip-cell {
            font-family: monospace;
            font-size: 12px;
            color: #666;
        }
        
        /* Статусы */
        .status-badge {
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            cursor: pointer;
        }
        
        .status-badge.new {
            background: #ffeb3b;
            color: #000;
        }
        
        .status-badge.processed {
            background: #4a6fa5;
            color: white;
        }
        
        .status-badge.completed {
            background: #4CAF50;
            color: white;
        }
        
        /* Кнопки действий */
        .action-buttons {
            display: flex;
            gap: 5px;
            white-space: nowrap;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        
        .btn-view {
            background: #4a6fa5;
            color: white;
        }
        
        .btn-view:hover {
            background: #3a5a8c;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-edit {
            background: #ffc107;
            color: #000;
        }
        
        .btn-edit:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }
        
        /* Модальное окно */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #000;
        }
        
        .modal-label {
            font-weight: 600;
            color: #4a6fa5;
            margin-bottom: 5px;
            margin-top: 15px;
        }
        
        .modal-label:first-of-type {
            margin-top: 0;
        }
        
        .modal-value {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            word-break: break-word;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        
        .modal-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            flex: 1;
        }
        
        .modal-btn.confirm {
            background: #dc3545;
            color: white;
        }
        
        .modal-btn.cancel {
            background: #6c757d;
            color: white;
        }
        
        .modal-btn:hover {
            transform: translateY(-2px);
        }
        
        /* Пагинация */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .page-btn {
            width: 40px;
            height: 40px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .page-btn:hover,
        .page-btn.active {
            background: #4a6fa5;
            color: white;
            border-color: #4a6fa5;
        }
        
        /* Адаптивность */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-buttons {
                display: flex;
                gap: 10px;
            }
            
            .filter-btn {
                flex: 1;
            }
            
            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_GET['deleted'])): ?>
            <div class="notification">✅ Заявка успешно удалена</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="notification">✅ Статус заявки обновлен</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="header">
            <h1>
                📋 Админ-панель заявок
                <span>v2.0</span>
            </h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total; ?></div>
                    <div class="stat-label">Всего заявок</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($new); ?></div>
                    <div class="stat-label">Новые</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($processed); ?></div>
                    <div class="stat-label">В обработке</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($completed); ?></div>
                    <div class="stat-label">Завершенные</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($today); ?></div>
                    <div class="stat-label">За сегодня</div>
                </div>
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">Все</button>
                <button class="filter-btn" data-filter="new">Новые</button>
                <button class="filter-btn" data-filter="processed">В обработке</button>
                <button class="filter-btn" data-filter="completed">Завершенные</button>
            </div>
            
            <div class="search-box">
                <input type="text" id="search" placeholder="Поиск по имени, телефону или email...">
            </div>
        </div>
        
        <div class="table-container">
            <table id="applicationsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Сообщение</th>
                        <th>Форма</th>
                        <th>IP</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr data-status="<?php echo $app['status']; ?>" class="status-<?php echo $app['status']; ?>">
                        <td class="id-cell">#<?php echo $app['id']; ?></td>
                        <td class="date-cell"><?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?></td>
                        <td class="name-cell" title="<?php echo htmlspecialchars($app['name']); ?>">
                            <?php echo htmlspecialchars(mb_substr($app['name'], 0, 30)) . (mb_strlen($app['name']) > 30 ? '...' : ''); ?>
                        </td>
                        <td class="phone-cell">
                            <a href="tel:<?php echo htmlspecialchars($app['phone']); ?>">
                                <?php echo htmlspecialchars($app['phone']); ?>
                            </a>
                        </td>
                        <td class="email-cell">
                            <?php if (!empty($app['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($app['email']); ?>" title="<?php echo htmlspecialchars($app['email']); ?>">
                                    <?php echo htmlspecialchars(mb_substr($app['email'], 0, 25)) . (mb_strlen($app['email']) > 25 ? '...' : ''); ?>
                                </a>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="message-cell" onclick="showMessage(<?php echo htmlspecialchars(json_encode($app['message'])); ?>)">
                            <?php if (!empty($app['message'])): ?>
                                <div class="message-preview" title="Кликните для просмотра">
                                    <?php echo htmlspecialchars(mb_substr($app['message'], 0, 30)) . (mb_strlen($app['message']) > 30 ? '...' : ''); ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="form-type <?php echo $app['form_type']; ?>">
                                <?php echo $app['form_type'] == 'modal' ? '📱 Мод' : '📝 Осн'; ?>
                            </span>
                        </td>
                        <td class="ip-cell" title="<?php echo htmlspecialchars($app['user_agent'] ?? ''); ?>">
                            <?php echo htmlspecialchars($app['ip_address'] ?? '—'); ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                <select name="status" onchange="this.form.submit()" class="status-badge <?php echo $app['status']; ?>">
                                    <option value="new" <?php echo $app['status'] == 'new' ? 'selected' : ''; ?>>Новая</option>
                                    <option value="processed" <?php echo $app['status'] == 'processed' ? 'selected' : ''; ?>>В обработке</option>
                                    <option value="completed" <?php echo $app['status'] == 'completed' ? 'selected' : ''; ?>>Завершена</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button onclick="viewDetails(<?php echo $app['id']; ?>)" class="btn btn-view" title="Просмотр">👁️</button>
                                <button onclick="deleteApplication(<?php echo $app['id']; ?>)" class="btn btn-delete" title="Удалить">🗑️</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="pagination" id="pagination"></div>
    </div>
    
    <!-- Модальное окно для просмотра сообщения -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">📝 Сообщение</h3>
            <div id="messageContent" class="modal-value"></div>
            <div class="modal-buttons">
                <button class="modal-btn cancel" onclick="closeMessageModal()">Закрыть</button>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для просмотра деталей -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">🔍 Детали заявки</h3>
            <div id="detailsContent"></div>
            <div class="modal-buttons">
                <button class="modal-btn cancel" onclick="closeDetailsModal()">Закрыть</button>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для удаления -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Подтверждение удаления</h3>
            <p>Вы уверены, что хотите удалить эту заявку? Это действие нельзя отменить.</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <div class="modal-buttons">
                    <button type="submit" class="modal-btn confirm">Удалить</button>
                    <button type="button" class="modal-btn cancel" onclick="closeModal()">Отмена</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Данные заявок для JS
        const applications = <?php echo json_encode($applications); ?>;
        
        // Фильтрация по статусу
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                const rows = document.querySelectorAll('#applicationsTable tbody tr');
                
                rows.forEach(row => {
                    if (filter === 'all' || row.dataset.status === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
        
        // Поиск по таблице
        document.getElementById('search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#applicationsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm) || searchTerm === '') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Просмотр сообщения
        function showMessage(message) {
            if (!message || message === 'null') {
                alert('Сообщение отсутствует');
                return;
            }
            document.getElementById('messageContent').innerHTML = '<p>' + message.replace(/\n/g, '<br>') + '</p>';
            document.getElementById('messageModal').classList.add('show');
        }
        
        function closeMessageModal() {
            document.getElementById('messageModal').classList.remove('show');
        }
        
        // Просмотр деталей
        function viewDetails(id) {
            const app = applications.find(a => a.id == id);
            if (!app) return;
            
            const details = `
                <div class="modal-label">ID:</div>
                <div class="modal-value">#${app.id}</div>
                
                <div class="modal-label">Имя:</div>
                <div class="modal-value">${app.name || '-'}</div>
                
                <div class="modal-label">Телефон:</div>
                <div class="modal-value">${app.phone || '-'}</div>
                
                <div class="modal-label">Email:</div>
                <div class="modal-value">${app.email || '-'}</div>
                
                <div class="modal-label">Сообщение:</div>
                <div class="modal-value">${app.message ? app.message.replace(/\n/g, '<br>') : '-'}</div>
                
                <div class="modal-label">Форма:</div>
                <div class="modal-value">${app.form_type === 'modal' ? 'Модальная' : 'Основная'}</div>
                
                <div class="modal-label">Дата:</div>
                <div class="modal-value">${new Date(app.created_at).toLocaleString('ru')}</div>
                
                <div class="modal-label">IP адрес:</div>
                <div class="modal-value">${app.ip_address || '-'}</div>
                
                <div class="modal-label">User Agent:</div>
                <div class="modal-value" style="font-size: 12px;">${app.user_agent || '-'}</div>
            `;
            
            document.getElementById('detailsContent').innerHTML = details;
            document.getElementById('detailsModal').classList.add('show');
        }
        
        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.remove('show');
        }
        
        // Удаление
        function deleteApplication(id) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }
        
        // Закрытие по клику вне модального окна
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        });
        
        // Закрытие по Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(modal => {
                    modal.classList.remove('show');
                });
            }
        });
        
        // Простая пагинация
        function setupPagination() {
            const rows = document.querySelectorAll('#applicationsTable tbody tr');
            if (rows.length <= 20) return;
            
            const rowsPerPage = 20;
            const pageCount = Math.ceil(rows.length / rowsPerPage);
            const pagination = document.getElementById('pagination');
            
            function showPage(page) {
                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                
                rows.forEach((row, index) => {
                    row.style.display = index >= start && index < end ? '' : 'none';
                });
            }
            
            pagination.innerHTML = '';
            for (let i = 1; i <= pageCount; i++) {
                const btn = document.createElement('button');
                btn.className = 'page-btn' + (i === 1 ? ' active' : '');
                btn.textContent = i;
                btn.onclick = () => {
                    document.querySelectorAll('.page-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    showPage(i);
                };
                pagination.appendChild(btn);
            }
            
            showPage(1);
        }
        
        // Раскомментируйте если нужно
        // setupPagination();
    </script>
</body>
</html>