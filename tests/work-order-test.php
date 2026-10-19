<?php
declare(strict_types=1);
require_once __DIR__ . '/../api/work-order.php';

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE TABLE works (id INTEGER PRIMARY KEY, sort_order INTEGER, created_at TEXT)');
$pdo->exec("INSERT INTO works VALUES (1, 1, '2026-10-19'), (2, 2, '2026-10-18'), (3, 3, '2026-10-17')");
$order = fn() => $pdo->query('SELECT id FROM works ORDER BY sort_order ASC, created_at DESC, id DESC')->fetchAll(PDO::FETCH_COLUMN);
saveWorkOrder($pdo, [3, 1, 2]);
check($order() === [3, 1, 2], 'Manual order must override dates');
foreach ([null, [], [1, 1, 2], [0, 1, 2], ['3', 1, 2], [[3], 1, 2], [1 => 3, 2 => 1, 3 => 2]] as $invalid) {
    try { saveWorkOrder($pdo, $invalid); throw new RuntimeException('Invalid input accepted'); }
    catch (InvalidArgumentException $e) {}
    check($order() === [3, 1, 2], 'Invalid request changed saved order');
}
foreach ([[1, 2], [1, 2, 4], [1, 2, 3, 4]] as $stale) {
    try { saveWorkOrder($pdo, $stale); throw new RuntimeException('Stale list accepted'); }
    catch (UnexpectedValueException $e) {}
    check(!$pdo->inTransaction(), 'Transaction left open');
    check($order() === [3, 1, 2], 'Stale request changed saved order');
}
$pdo->exec("CREATE TRIGGER reject_order BEFORE UPDATE ON works WHEN NEW.id = 1 BEGIN SELECT RAISE(ABORT, 'test failure'); END");
try { saveWorkOrder($pdo, [2, 1, 3]); throw new RuntimeException('Failed update accepted'); }
catch (PDOException $e) {}
check($order() === [3, 1, 2], 'Partial update was not rolled back');
$pdo->exec('DROP TRIGGER reject_order');
$pdo->exec("INSERT INTO works SELECT 4, COALESCE(MAX(sort_order), 0) + 1, '2026-10-20' FROM works");
check($order() === [3, 1, 2, 4], 'New work must be appended');
saveWorkOrder($pdo, [4, 2, 1, 3]);
check($order() === [4, 2, 1, 3], 'Second order was not saved');
echo "PASS: manual order, invalid input, stale lists, transaction rollback and new works at the end.\n";
