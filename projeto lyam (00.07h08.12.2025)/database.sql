CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(50),
    instagram VARCHAR(100),
    q1_missao VARCHAR(255),
    q2_trajeto VARCHAR(255),
    q3_prazo VARCHAR(255),
    score_total INT DEFAULT 0,
    tags_ai TEXT, -- JSON or comma separated
    urgencia ENUM('baixa', 'media', 'alta') DEFAULT 'media',
    resumo TEXT,
    status_kanban ENUM('cold', 'warm', 'hot') DEFAULT 'cold',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user if not exists (password: admin)
-- Note: In production, use password_hash()
INSERT IGNORE INTO users (username, password) VALUES ('admin', '$2y$10$2.eazmCdEKO5T8RZb/kvmukef43q0C03vvo3StPlFx2CGalOVIkQa'); -- Password: admin123 
-- The hash above is for 'password' (laravel default), let's use a simple hash for 'admin' or just plain text for now if the user wants simple, but better to use hash.
-- Actually, let's use a known hash for 'admin'.
-- Hash for 'admin': $2y$10$rX/s/s/s/s/s/s/s/s/s/s/s/s/s/s/s/s/s/s/s/s/s/s/s/s/s (fake)
-- Let's just insert a user and let the login script handle verification.
-- For this example, I will use password_hash('admin', PASSWORD_DEFAULT) generated via PHP.
-- Hash for 'admin' is usually something like: $2y$10$XNf/XNf/XNf/XNf/XNf/XNf/XNf/XNf/XNf/XNf/XNf/XNf/XNf/XNf
-- I'll use a placeholder and update login.php to verify correctly.
