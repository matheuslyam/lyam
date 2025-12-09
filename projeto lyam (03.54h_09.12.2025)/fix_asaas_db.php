<?php
// fix_asaas_db.php
require_once 'config.php';

try {
    echo "<h1>🔧 Reparando Banco de Dados (Módulo Asaas)</h1>";

    $columns = [
        'asaas_customer_id' => 'VARCHAR(50) DEFAULT NULL',
        'asaas_payment_id' => 'VARCHAR(50) DEFAULT NULL',
        'payment_link' => 'VARCHAR(255) DEFAULT NULL',
        'payment_status' => 'VARCHAR(50) DEFAULT "Pendente"',
        'payment_due_date' => 'DATE DEFAULT NULL'
    ];

    foreach ($columns as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE leads ADD COLUMN $col $def");
            echo "<div style='color:green'>✅ Coluna <b>$col</b> criada com sucesso.</div>";
        } catch (PDOException $e) {
            // Código 1060 = Duplicate column name (já existe)
            if (strpos($e->getMessage(), '1060') !== false) {
                echo "<div style='color:blue'>ℹ️ Coluna <b>$col</b> já existe.</div>";
            } else {
                echo "<div style='color:red'>❌ Erro ao criar $col: " . $e->getMessage() . "</div>";
            }
        }
    }

    echo "<br><h3>Pronto! Tente acessar o CRM agora.</h3>";
    echo "<a href='admin/leads.php'>Voltar para Leads</a>";

} catch (Exception $e) {
    die("Erro Fatal: " . $e->getMessage());
}
?>