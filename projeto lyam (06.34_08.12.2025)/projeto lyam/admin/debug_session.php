<?php
session_start();
echo "<h1>Raio-X da Sessão</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<a href='logout.php'>Fazer Logout e Limpar Tudo</a>";
?>