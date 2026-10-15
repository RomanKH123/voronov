CREATE TABLE IF NOT EXISTS leads (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    package VARCHAR(100) NOT NULL,
    name VARCHAR(80) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    status ENUM('new', 'contacted', 'closed') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    INDEX idx_leads_created_at (created_at),
    INDEX idx_leads_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    scope VARCHAR(40) NOT NULL,
    identifier CHAR(64) NOT NULL,
    created_at BIGINT UNSIGNED NOT NULL,
    INDEX idx_rate_limits_lookup (scope, identifier, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
