<?php
// update_db_asaas.php
require_once 'config.php';

try {
    echo "<h1>💳 Preparando Banco para Integração Financeira (Asaas)...</h1>";

    $new_columns = [
        'asaas_customer_id' => 'VARCHAR(50) DEFAULT NULL COMMENT "ID do Cliente no Asaas"',
        'asaas_payment_id' => 'VARCHAR(50) DEFAULT NULL COMMENT "ID da Cobrança no Asaas"',
        'payment_link' => 'VARCHAR(255) DEFAULT NULL COMMENT "URL da Fatura/Link"',
        'payment_status' => 'VARCHAR(20) DEFAULT "PENDING" COMMENT "Status do Pagamento"',
        'payment_due_date' => 'DATE DEFAULT NULL COMMENT "Data de Vencimento"'
    ];

    foreach ($new_columns as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE leads ADD COLUMN $col $def");
            echo "<div style='color:green'>✅ Coluna <b>$col</b> criada com sucesso.</div>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), '1060') !== false) {
                echo "<div style='color:orange'>ℹ️ Coluna <b>$col</b> já existe.</div>";
            } else {
                echo "<div style='color:red'>❌ Erro em $col: " . $e->getMessage() . "</div>";
            }
        }
    }

    echo "<br><h3>Infraestrutura Financeira Pronta! 🚀</h3>";
    echo "<a href='admin/leads.php'>Voltar ao Painel</a>";

} catch (PDOException $e) {
    die("Erro Crítico: " . $e->getMessage());
}
?>