<?php
$case = $argv[1] ?? 'valid';
$pdo = new PDO('sqlite::memory:'); $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("CREATE TABLE applications (id INTEGER PRIMARY KEY, status TEXT); INSERT INTO applications VALUES (1, 'new'), (2, 'new'), (3, 'completed')");
function db() { global $pdo; return $pdo; }
function logAction($action, $details = '') {}
function verifyCSRFToken($token) { return $token === 'test-csrf'; }
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SESSION = [];
$_POST = ['action' => 'bulk_status', 'csrf_token' => 'test-csrf', 'ids' => ['1','2'], 'status' => 'processed'];
if ($case === 'bad-csrf') $_POST['csrf_token'] = 'wrong';
if ($case === 'bad-status') $_POST['status'] = 'unknown';
if ($case === 'bad-id') $_POST['ids'] = ['1', ['2']];
if ($case === 'over-limit') $_POST['ids'] = range(1, 101);
if ($case === 'empty') $_POST['ids'] = [];
register_shutdown_function(function () use ($pdo, $case) {
    $actual = $pdo->query('SELECT status FROM applications ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    $expected = $case === 'valid' ? ['processed','processed','completed'] : ['new','new','completed'];
    if ($actual !== $expected) { echo "FAIL: bulk $case\n"; exit(1); }
    echo "PASS: bulk $case\n";
});
$source = file_get_contents(__DIR__ . '/../admin/index.php');
$start = strpos($source, '// Обработка действий');
$end = strpos($source, '// Получение данных', $start);
eval(substr($source, $start, $end - $start));
