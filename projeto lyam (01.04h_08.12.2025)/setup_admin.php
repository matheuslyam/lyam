<?php
// Arquivo utilitário para criar o primeiro admin
// DELETAR ESTE ARQUIVO APÓS O USO!
require_once 'config.php';

// Configuração do Admin
$novo_usuario = 'admin';
$nova_senha = 'password'; // Senha inicial (mude depois se quiser)

try {
    // 1. Verifica se a tabela existe (cria se não existir)
    $sql_create = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql_create);
    echo "<div>1. Verificação de tabela 'users': OK.</div>";

    // 2. Verifica se o usuário já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$novo_usuario]);

    if ($stmt->fetch()) {
        echo "<div style='color:orange'>2. Usuário '$novo_usuario' já existe. Nenhuma ação tomada.</div>";
    } else {
        // 3. Cria o hash seguro da senha
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        // 4. Insere no banco
        $stmt_insert = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt_insert->execute([$novo_usuario, $hash]);

        echo "<div style='color:green; font-weight:bold;'>3. SUCESSO! Usuário '$novo_usuario' criado.</div>";
        echo "<div>Senha definida: <strong>$nova_senha</strong></div>";
    }

    echo "<br><hr><br>";
    echo "<a href='admin/login.php' style='font-size:20px; background:blue; color:white; padding:10px; text-decoration:none;'>Ir para Login &rarr;</a>";

} catch (PDOException $e) {
    die("Erro no Banco de Dados: " . $e->getMessage());
}
?>