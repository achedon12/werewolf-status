CREATE TABLE IF NOT EXISTS endpoints (
                                         id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                         name VARCHAR(150) NOT NULL,
                                         check_url VARCHAR(500) NOT NULL,
                                         public_url VARCHAR(500) NULL,

                                         uptime_unit VARCHAR(50) NOT NULL DEFAULT 'seconds',

                                         is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                                         discord_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1,
                                         discord_webhook_url VARCHAR(500) NULL,

                                         created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                         INDEX idx_endpoints_is_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS downtimes (
                                         id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                         endpoint_id INT UNSIGNED NOT NULL,

                                         down_at DATETIME NOT NULL,
                                         up_at DATETIME NULL,

                                         http_code INT NULL,
                                         reason VARCHAR(500) NULL,

                                         discord_down_notified_at DATETIME NULL,
                                         discord_up_notified_at DATETIME NULL,

                                         created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                                         CONSTRAINT fk_downtimes_endpoint
                                             FOREIGN KEY (endpoint_id)
                                                 REFERENCES endpoints(id)
                                                 ON DELETE CASCADE,

                                         INDEX idx_downtimes_endpoint_id (endpoint_id),
                                         INDEX idx_downtimes_down_at (down_at),
                                         INDEX idx_downtimes_up_at (up_at),
                                         INDEX idx_downtimes_open (endpoint_id, up_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
                                        setting_key VARCHAR(100) PRIMARY KEY,
                                        setting_value VARCHAR(255) NOT NULL,
                                        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
                                           id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                           username VARCHAR(100) NOT NULL UNIQUE,
                                           password_hash VARCHAR(255) NOT NULL,

                                           role VARCHAR(50) NOT NULL DEFAULT 'admin',
                                           is_enabled TINYINT(1) NOT NULL DEFAULT 1,

                                           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                           INDEX idx_admin_users_username (username),
                                           INDEX idx_admin_users_is_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (
    setting_key,
    setting_value,
    updated_at
) VALUES (
             'display_period_hours',
             '48',
             NOW()
         )
ON DUPLICATE KEY UPDATE
    setting_value = setting_value;

INSERT INTO settings (
    setting_key,
    setting_value,
    updated_at
) VALUES (
             'status_check_interval',
             '30',
             NOW()
         )
ON DUPLICATE KEY UPDATE
    setting_value = setting_value;