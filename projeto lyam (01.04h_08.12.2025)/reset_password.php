<?php
require_once 'config.php';

// Senha para resetar
$new_pass = 'Suehtam$$3002';
$hash = password_hash($new_pass, PASSWORD_DEFAULT);

try {
    // Atualiza a senha do admin existente
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);

    echo "<div style='font-family: sans-serif; padding: 20px; text-align: center;'>";
    echo "<h1 style='color: #a3e635; background: #0b1726; padding: 10px; border-radius: 8px; display: inline-block;'>Senha Redefinida!</h1>";
    echo "<p>A senha do usuário <strong>admin</strong> foi atualizada para: <strong>$new_pass</strong></p>";
    echo "<br>";
    echo "<a href='admin/login.php' style='background: #0b1726; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Ir para Login &rarr;</a>";
    echo "</div>";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>