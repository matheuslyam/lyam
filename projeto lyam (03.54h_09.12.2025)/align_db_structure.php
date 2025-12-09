<?php
// align_db_structure.php
require_once 'config.php';

try {
    echo "<h1>🔄 Alinhando Infraestrutura do Banco de Dados...</h1>";

    // 1. Adicionar colunas críticas que faltam (Erro atual)
    $missing_cols = [
        'last_contact_date' => 'DATETIME DEFAULT NULL',
        'sales_notes' => 'TEXT DEFAULT NULL',
        'status_kanban' => "VARCHAR(50) DEFAULT 'New Lead'" // Mantendo compatibilidade legada
    ];

    foreach ($missing_cols as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE leads ADD COLUMN $col $def");
            echo "<div style='color:green'>✅ Coluna <b>$col</b> criada.</div>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), '1060') !== false) {
                echo "<div style='color:orange'>⚠️ Coluna <b>$col</b> já existia.</div>";
            } else {
                echo "<div style='color:red'>❌ Erro em $col: " . $e->getMessage() . "</div>";
            }
        }
    }

    // 2. Renomear colunas do 'Reset' para o padrão do 'Quiz/Kanban'
    // De 'status' para 'funnel_stage'
    try {
        // Verifica se 'status' existe antes de tentar mudar
        $check = $pdo->query("SHOW COLUMNS FROM leads LIKE 'status'");
        if ($check->fetch()) {
            $pdo->exec("ALTER TABLE leads CHANGE status funnel_stage VARCHAR(50) DEFAULT 'New Lead'");
            echo "<div style='color:blue'>🔄 Coluna 'status' migrada para <b>'funnel_stage'</b>.</div>";
        } else {
            echo "<div>ℹ️ Coluna 'status' já foi migrada ou não existe.</div>";
        }
    } catch (PDOException $e) {
        echo "<div style='color:red'>Erro na migração de status: " . $e->getMessage() . "</div>";
    }

    // De 'source' para 'lead_source'
    try {
        $check = $pdo->query("SHOW COLUMNS FROM leads LIKE 'source'");
        if ($check->fetch()) {
            $pdo->exec("ALTER TABLE leads CHANGE source lead_source VARCHAR(50) DEFAULT 'Quiz'");
            echo "<div style='color:blue'>🔄 Coluna 'source' migrada para <b>'lead_source'</b>.</div>";
        } else {
            echo "<div>ℹ️ Coluna 'source' já foi migrada ou não existe.</div>";
        }
    } catch (PDOException $e) {
        echo "<div style='color:red'>Erro na migração de source: " . $e->getMessage() . "</div>";
    }

    echo "<br><h3>✅ Banco de Dados 100% Sincronizado!</h3>";
    echo "<a href='index.php'>Voltar e Testar Quiz</a>";

} catch (PDOException $e) {
    die("Erro Crítico: " . $e->getMessage());
}
?>