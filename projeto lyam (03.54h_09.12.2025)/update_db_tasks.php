<?php
// update_db_tasks.php
require_once 'config.php';

try {
    echo "<h1>📅 Adicionando Agendador de Tarefas...</h1>";

    $cols = [
        'next_followup_date' => 'DATETIME DEFAULT NULL COMMENT "Data/Hora do próximo contato"',
        'next_followup_type' => 'VARCHAR(50) DEFAULT NULL COMMENT "Tipo: whatsapp, ligacao, email"',
        'next_followup_note' => 'TEXT DEFAULT NULL COMMENT "O que fazer (ex: cobrar boleto)"'
    ];

    foreach ($cols as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE leads ADD COLUMN $col $def");
            echo "<div style='color:green'>✅ Coluna <b>$col</b> criada.</div>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), '1060') !== false) {
                echo "<div style='color:blue'>ℹ️ Coluna <b>$col</b> já existe.</div>";
            } else {
                echo "<div style='color:red'>❌ Erro em $col: " . $e->getMessage() . "</div>";
            }
        }
    }

    echo "<br><h3>Agenda Pronta! Vamos configurar a interface. 🚀</h3>";
    echo "<a href='admin/leads.php'>Voltar</a>";

} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}
?>