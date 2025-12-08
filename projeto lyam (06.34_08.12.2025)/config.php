<?php
// Linha 1: A tag de abertura DEVE ser a primeira coisa no arquivo.

// --- CONFIGURAÇÃO CRÍTICA DE DEBUG ---
// ESTAS LINHAS FORÇAM O PHP A MOSTRAR OS ERROS FATAIS (REMOVER EM PRODUÇÃO)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ------------------------------------


// ------------------------------------
// 1. Configurações de Banco de Dados
// ------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'projetolyam');
define('DB_USER', 'root');
define('DB_PASS', '');

// ------------------------------------
// 2. Configurações de Fuso Horário (BRT)
// ------------------------------------
// Garante que todas as datas e horas PHP no backend usem o fuso horário de Brasília (GMT-3).
date_default_timezone_set('America/Sao_Paulo');

// ------------------------------------
// 2.1 Helper Functions
// ------------------------------------
if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('formatPhoneNumber')) {
    function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/\D/', '', $phone);
        $len = strlen($phone);
        if ($len === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
        } elseif ($len === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
        }
        return $phone;
    }
}

// ------------------------------------
// 3. Conexão ao Banco de Dados (PDO)
// ------------------------------------
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Aqui paramos a execução se a conexão falhar.
    die("Database Connection Failed: " . $e->getMessage());
}

// O arquivo DEVE terminar aqui, sem a tag de fechamento ?>