<?php
// update_db_utms.php
require_once 'config.php';

try {
    echo "<h1>Atualizando Estrutura para UTMs...</h1>";

    $columns = [
        'utm_source' => 'VARCHAR(255) DEFAULT NULL',
        'utm_medium' => 'VARCHAR(255) DEFAULT NULL',
        'utm_campaign' => 'VARCHAR(255) DEFAULT NULL',
        'utm_content' => 'VARCHAR(255) DEFAULT NULL',
        'utm_term' => 'VARCHAR(255) DEFAULT NULL'
    ];

    foreach ($columns as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE leads ADD COLUMN $col $def");
            echo "<div style='color:green'>✅ Coluna <b>$col</b> criada.</div>";
        } catch (PDOException $e) {
            // Se der erro 1060, é porque já existe, então ignoramos
            if (strpos($e->getMessage(), '1060') !== false) {
                echo "<div style='color:orange'>⚠️ Coluna <b>$col</b> já existe.</div>";
            } else {
                echo "<div style='color:red'>❌ Erro em $col: " . $e->getMessage() . "</div>";
            }
        }
    }

    echo "<br><h3>Concluído! Agora o banco aceita dados de tráfego.</h3>";
    echo "<a href='index.php'>Voltar ao Quiz</a>";

} catch (PDOException $e) {
    die("Erro Crítico: " . $e->getMessage());
}
?>