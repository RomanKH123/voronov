<?php
// Copy to config.local.php on the server. Never commit the local file.
return [
    'db_host' => 'localhost',
    'db_name' => 'CHANGE_ME',
    'db_user' => 'CHANGE_ME',
    'db_pass' => 'CHANGE_ME',
    'admin_login' => 'admin',
    // Generate with: php -r "echo password_hash('NEW_LONG_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
    'admin_password_hash' => 'CHANGE_ME',
    // Plaintext fallback for hosts where a password hash cannot be generated. Prefer the hash above.
    'admin_password' => '',
    'allowed_origin' => 'https://voronov-art.ru',
];
