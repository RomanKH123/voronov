<?php
declare(strict_types=1);
require_once __DIR__ . '/../CMM/auth.php';
require_once __DIR__ . '/../api/portfolio-validation.php';

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE cmm_users (id INTEGER PRIMARY KEY, login TEXT, password_hash TEXT, name TEXT, role TEXT)');
$insert = $pdo->prepare('INSERT INTO cmm_users VALUES (1, ?, ?, ?, ?)');
$hash = password_hash('test-password-123', PASSWORD_DEFAULT);
$insert->execute(['admin', $hash, 'Administrator', 'admin']);
$user = $pdo->query('SELECT * FROM cmm_users WHERE id = 1')->fetch();
$signedIn = ['cmm_auth' => true, 'cmm_user_id' => 1, 'cmm_user_role' => 'admin',
    'cmm_auth_fingerprint' => cmmAuthFingerprint($user), 'unrelated' => 'keep'];
$_SESSION = $signedIn;
check(cmmSessionValid($pdo), 'Valid session rejected');
$pdo->exec("UPDATE cmm_users SET name = 'Updated' WHERE id = 1");
check(cmmSessionValid($pdo) && $_SESSION['cmm_user_name'] === 'Updated', 'Display name not refreshed');
$pdo->exec("UPDATE cmm_users SET role = 'editor' WHERE id = 1");
check(!cmmSessionValid($pdo), 'Downgraded admin retained access');
check(!isset($_SESSION['cmm_auth']) && $_SESSION['unrelated'] === 'keep', 'Revoked session not cleared correctly');
$pdo->exec("UPDATE cmm_users SET role = 'admin' WHERE id = 1");
$_SESSION = $signedIn;
$pdo->exec("UPDATE cmm_users SET password_hash = 'different-hash' WHERE id = 1");
check(!cmmSessionValid($pdo), 'Password change did not revoke session');
$_SESSION = $signedIn;
unset($_SESSION['cmm_auth_fingerprint']);
check(!cmmSessionValid($pdo), 'Legacy session accepted without verification');
$_SESSION = $signedIn;
$pdo->exec('DELETE FROM cmm_users WHERE id = 1');
check(!cmmSessionValid($pdo), 'Deleted account retained access');
$_SESSION = [];
check(!cmmSessionValid($pdo), 'Anonymous session accepted');

foreach (['', '/img/prewiu/project.webp', '/img/photo%20one.png', 'https://example.com/image.png', 'http://example.com/image.jpg'] as $url) {
    check(validPortfolioImage($url), 'Safe image rejected: ' . $url);
}
foreach (['javascript:alert(1)', 'data:image/svg+xml,test', '//example.com/a', '/\\example.com/a', "https://example.com/a\n", '/img/a" onerror="test', 'https://user:pass@example.com/a', 'file:///etc/passwd'] as $url) {
    check(!validPortfolioImage($url), 'Unsafe image accepted: ' . $url);
}
echo "PASS: valid sessions, role/password revocation, deleted accounts, legacy sessions, anonymous access, image URL validation.\n";
