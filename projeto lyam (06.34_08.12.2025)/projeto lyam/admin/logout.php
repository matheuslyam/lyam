<?php
// /admin/logout.php
session_start();

// 1. Limpa todas as variáveis de sessão da memória imediatamente
$_SESSION = array();

// 2. Mata o cookie da sessão no navegador (Protocolo de Blindagem)
// Isso garante que o ID da sessão antiga não possa ser reutilizado.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destrói a sessão no armazenamento do servidor
session_destroy();

// 4. Redireciona para o login
header("Location: login.php");
exit;
?>