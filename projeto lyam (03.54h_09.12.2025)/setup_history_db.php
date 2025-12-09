<?php
// setup_history_db.php
require_once 'config.php';

try {
    echo "<h1>📜 Criando Tabela de Histórico Unificado...</h1>";

    $sql = "CREATE TABLE IF NOT EXISTS interactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT NOT NULL,
        user_id INT NULL, -- Pode ser NULL se for ação do Sistema (ex: Webhook)
        type VARCHAR(50) NOT NULL, -- 'note', 'status_change', 'payment', 'whatsapp', 'system'
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "<div style='color:green'>✅ Tabela <b>interactions</b> criada com sucesso.</div>";

    echo "<br><h3>A Memória do Sistema está ativa! 🧠</h3>";
    echo "<a href='admin/leads.php'>Voltar ao Painel</a>";

} catch (PDOException $e) {
    die("Erro Crítico: " . $e->getMessage());
}
?>