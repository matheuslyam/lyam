<?php
// upgrade_quiz_db.php
require_once 'config.php';

try {
    echo "<h1>Atualizando Banco para Suporte ao Quiz...</h1>";

    // Lista de colunas novas necessárias
    $new_columns = [
        'instagram' => 'VARCHAR(255) DEFAULT NULL',
        'q1_missao' => 'TEXT DEFAULT NULL',
        'q2_trajeto' => 'TEXT DEFAULT NULL',
        'q3_prazo' => 'TEXT DEFAULT NULL',
        'score_total' => 'INT DEFAULT 0',
        'tags_ai' => 'TEXT DEFAULT NULL',
        'urgencia' => 'VARCHAR(50) DEFAULT NULL',
        'resumo' => 'TEXT DEFAULT NULL'
    ];

    foreach ($new_columns as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE leads ADD COLUMN $col $def");
            echo "<div style='color:green'>✅ Coluna <b>$col</b> criada.</div>";
        } catch (PDOException $e) {
            // Ignora se já existir
            if (strpos($e->getMessage(), '1060') !== false) {
                echo "<div style='color:orange'>⚠️ Coluna <b>$col</b> já existe.</div>";
            } else {
                echo "<div style='color:red'>❌ Erro ao criar $col: " . $e->getMessage() . "</div>";
            }
        }
    }

    echo "<br><h3>Banco de dados pronto para o Quiz!</h3>";
    echo "<a href='index.php'>Voltar ao Início</a>";

} catch (PDOException $e) {
    die("Erro Crítico: " . $e->getMessage());
}
?>