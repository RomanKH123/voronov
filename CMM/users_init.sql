CREATE TABLE IF NOT EXISTS cmm_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) DEFAULT '',
    role ENUM('admin', 'editor') DEFAULT 'editor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Пароль по умолчанию: 093093093Rk (захеширован через password_hash с PASSWORD_BCRYPT)
-- Чтобы сгенерировать хеш нового пароля: php -r "echo password_hash('ваш_пароль', PASSWORD_BCRYPT);"
INSERT INTO cmm_users (login, password_hash, name, role) VALUES
('admin', '$2y$10$PLACEHOLDER', 'Администратор', 'admin');

-- ВАЖНО: после создания таблицы выполните в PHP или CLI:
-- UPDATE cmm_users SET password_hash = '$2y$10$...' WHERE login = 'admin';
-- Или воспользуйтесь скриптом setup.php для автоматической установки.
