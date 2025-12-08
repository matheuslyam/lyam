CREATE DATABASE IF NOT EXISTS projetolyam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE projetolyam;

-- Tabela de Usuários Administrativos
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Leads (Com campos do CRM Pro V2)
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    telefone VARCHAR(50) NOT NULL,
    instagram VARCHAR(255),
    
    -- Respostas do Quiz
    q1_missao TEXT,
    q2_trajeto TEXT,
    q3_prazo TEXT,
    
    -- Pontuação e IA
    score_total INT,
    tags_ai JSON,
    urgencia VARCHAR(50),
    resumo TEXT,
    
    -- Campos CRM
    status_kanban VARCHAR(50) DEFAULT 'cold',
    funnel_stage VARCHAR(50) DEFAULT 'New Lead',
    last_contact_date DATETIME DEFAULT NULL,
    sales_notes TEXT,
    lead_source VARCHAR(50) DEFAULT 'Quiz',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
