<?php
// convert_db_to_english.php
require_once 'config.php';

try {
    echo "<h1>🇺🇸 Converting Database to International Standard...</h1>";

    // 1. Dicionário de Tradução (Português -> Inglês)
    $translations = [
        'nome' => 'name',
        'telefone' => 'phone',
        'origem' => 'lead_source',
        'source' => 'lead_source', // Padronizando 'source' para 'lead_source' para evitar conflitos de palavra reservada
        'status' => 'funnel_stage',
        'status_kanban' => 'funnel_stage',
        'q1_missao' => 'quiz_mission',
        'q2_trajeto' => 'quiz_path',
        'q3_prazo' => 'quiz_timeline',
        'urgencia' => 'urgency',
        'resumo' => 'summary'
    ];

    $table = 'leads';

    // Pega as colunas atuais
    $stmt = $pdo->query("DESCRIBE $table");
    $current_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($translations as $pt => $en) {
        if (in_array($pt, $current_columns)) {
            // Se a coluna em PT existe, renomeia para EN
            try {
                // Mantém o tipo original (VARCHAR/TEXT) simplificado
                $type = ($pt === 'resumo' || strpos($pt, 'q') === 0) ? 'TEXT' : 'VARCHAR(255)';
                $pdo->exec("ALTER TABLE $table CHANGE $pt $en $type DEFAULT NULL");
                echo "<div style='color:blue'>🔄 Renamed <b>$pt</b> to <b>$en</b>.</div>";
            } catch (PDOException $e) {
                echo "<div style='color:red'>Error renaming $pt: " . $e->getMessage() . "</div>";
            }
        } elseif (!in_array($en, $current_columns)) {
            // Se nem a PT nem a EN existem, cria a EN direto
            $type = ($en === 'summary' || strpos($en, 'quiz_') === 0) ? 'TEXT' : 'VARCHAR(255)';
            $pdo->exec("ALTER TABLE $table ADD COLUMN $en $type DEFAULT NULL");
            echo "<div style='color:green'>✅ Created column <b>$en</b>.</div>";
        } else {
            echo "<div style='color:gray'>ℹ️ Column <b>$en</b> already exists.</div>";
        }
    }

    // 2. Garante Colunas de Infraestrutura (UTMs e System)
    $infra_columns = [
        'email' => 'VARCHAR(255)',
        'instagram' => 'VARCHAR(255)',
        'score_total' => 'INT DEFAULT 0',
        'tags_ai' => 'TEXT', // JSON
        'last_contact_date' => 'DATETIME',
        'sales_notes' => 'TEXT',
        'utm_source' => 'VARCHAR(255)',
        'utm_medium' => 'VARCHAR(255)',
        'utm_campaign' => 'VARCHAR(255)',
        'utm_content' => 'VARCHAR(255)',
        'utm_term' => 'VARCHAR(255)'
    ];

    foreach ($infra_columns as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $col $def");
            echo "<div style='color:green'>✅ Checked/Created <b>$col</b>.</div>";
        } catch (Exception $e) {
            // Ignora erro se já existir
        }
    }

    echo "<br><h3>See? Everything is in English now. 🇺🇸</h3>";
    echo "<a href='index.php'>Go to Quiz</a>";

} catch (PDOException $e) {
    die("Fatal Error: " . $e->getMessage());
}
?>