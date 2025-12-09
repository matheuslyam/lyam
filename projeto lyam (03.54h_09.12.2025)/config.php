<?php
// config.php - Arquivo Mestre de Configuração e Conexão

// 1. Configurações de Erro (Debug)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Credenciais do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'projetolyam');
define('DB_USER', 'root');
define('DB_PASS', '');

// 3. Integração ASAAS (Financeiro)
// Mude para 'https://api.asaas.com/v3' em Produção
define('ASAAS_URL', 'https://sandbox.asaas.com/api/v3');
// Coloque sua chave API aqui:
define('ASAAS_API_KEY', '$aact_hmlg_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6OjE5YTFiYTMyLTNjOWItNGYwNy1iNTk4LWMxZGZlZDUyM2Y1NDo6JGFhY2hfYzAyZDE1OWEtNzMyZi00N2M3LWIwMWEtMjkwNGU1YTdkOTYw');

// ... (dentro do config.php, abaixo da API Key)

// Token de Segurança para o Webhook (Crie uma senha forte)
define('ASAAS_WEBHOOK_TOKEN', 'lyamsegredowebhook3002');

// 4. Configurações Regionais
date_default_timezone_set('America/Sao_Paulo');

// 5. Conexão PDO (O Coração do Sistema)
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Se falhar, mata o processo aqui mesmo para não gerar erros em cascata
    die("Erro Crítico de Conexão: " . $e->getMessage());
}

// 6. Funções Globais (Helpers)
// Estas funções ficam disponíveis para TODO o sistema

if (!function_exists('jsonResponse')) {
    function jsonResponse($success, $message, $data = [])
    {
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode(["success" => $success, "message" => $message, "data" => $data]);
        exit;
    }
}

if (!function_exists('formatPhoneNumber')) {
    function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/\D/', '', $phone ?? '');
        $len = strlen($phone);
        if ($len === 11) { // Celular (11 91234-5678)
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7, 4);
        } elseif ($len === 10) { // Fixo (11 1234-5678)
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6, 4);
        }
        return $phone; // Retorna original se não casar padrão
    }
}

if (!function_exists('getStageBadge')) {
    function getStageBadge($stage)
    {
        $stage = $stage ?? 'New Lead';

        // Definição de Cores (Tailwind CSS)
        $colors = match ($stage) {
            'New Lead' => 'text-blue-400 bg-blue-400/10 border-blue-400/20',
            'Contact Attempt' => 'text-yellow-400 bg-yellow-400/10 border-yellow-400/20',
            'Qualified' => 'text-purple-400 bg-purple-400/10 border-purple-400/20',
            'Negotiation' => 'text-orange-400 bg-orange-400/10 border-orange-400/20',
            'Proposal Sent' => 'text-pink-400 bg-pink-400/10 border-pink-400/20',
            'Won' => 'text-green-400 bg-green-400/10 border-green-400/20',
            'Lost' => 'text-red-400 bg-red-400/10 border-red-400/20',
            default => 'text-gray-400 bg-gray-400/10 border-gray-400/20'
        };

        // Tradução para Exibição
        $labels = [
            'New Lead' => 'Novo Lead',
            'Contact Attempt' => 'Tentativa',
            'Qualified' => 'Qualificado',
            'Negotiation' => 'Negociação',
            'Proposal Sent' => 'Proposta',
            'Won' => 'Venda Feita',
            'Lost' => 'Perdido'
        ];

        $label = $labels[$stage] ?? $stage;

        return "<span class='px-3 py-1 rounded-lg text-xs font-bold border $colors uppercase tracking-wider'>$label</span>";
    }
}
?>