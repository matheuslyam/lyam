-- Adiciona a tabela de usuários se não existir
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'seller') DEFAULT 'seller',
    commission_rate DECIMAL(5,2) DEFAULT 0.00,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Garante que o admin padrão exista (senha: password)
-- Se já existir, este comando será ignorado
INSERT IGNORE INTO users (username, password, full_name, role) 
VALUES ('admin', '$2y$10$2.eazmCdEKO5T8RZb/kvmukef43q0C03vvo3StPlFx2CGalOVIkQa', 'Administrador', 'admin');