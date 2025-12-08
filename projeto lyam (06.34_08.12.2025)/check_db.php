<?php
require_once 'config.php';

try {
    echo "<h1>Diagnóstico de Banco de Dados</h1>";

    // Lista colunas da tabela leads
    $stmt = $pdo->query("DESCRIBE leads");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $colunas_esperadas = [
        'user_id',
        'status_kanban',
        'utm_source',
        'sale_value',
        'asaas_payment_id',
        'invoice_file',
        'last_contact_date'
    ];

    echo "<h2>Tabela LEADS:</h2>";
    echo "<ul>";
    foreach ($columns as $col) {
        $status = in_array($col, $colunas_esperadas) ? "✅ <b>NOVA</b>" : "Existente";
        echo "<li>$col ($status)</li>";
    }
    echo "</ul>";

    // Verifica se tabela users existe
    $stmt_users = $pdo->query("DESCRIBE users");
    if ($stmt_users) {
        echo "<h2>Tabela USERS:</h2> <h3 style='color:green'>EXISTE ✅</h3>";
    }

} catch (PDOException $e) {
    echo "<h2 style='color:red'>ERRO: " . $e->getMessage() . "</h2>";
}
?>