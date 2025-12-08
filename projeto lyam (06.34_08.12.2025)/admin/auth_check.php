<?php
// /admin/auth_check.php

// Inicia a sessão apenas se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verificação Básica: Usuário existe na sessão?
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Mata a sessão por segurança e redireciona
    session_unset();
    session_destroy();
    header("Location: login.php?msg=auth_required");
    exit;
}

// 2. Segurança: Timeout de Inatividade (30 minutos = 1800 segundos)
$timeout_duration = 1800;

if (isset($_SESSION['last_activity'])) {
    $elapsed_time = time() - $_SESSION['last_activity'];

    if ($elapsed_time > $timeout_duration) {
        // Tempo expirado
        session_unset();
        session_destroy();
        header("Location: login.php?msg=timeout");
        exit;
    }
}

// Atualiza o carimbo de tempo da última atividade para renovar o "timer"
$_SESSION['last_activity'] = time();

// 3. Opcional: Verificação de Fingerprint (User Agent) para evitar roubo de sessão
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
} elseif ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    // Se o navegador mudou repentinamente, pode ser um ataque de sequestro de sessão
    session_unset();
    session_destroy();
    header("Location: login.php?msg=security_alert");
    exit;
}
?>