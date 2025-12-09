<?php
// webhook_asaas.php - O "Ouvido" Automático do CRM
require_once 'config.php';

// Habilita resposta JSON
header("Content-Type: application/json");

// Log para Auditoria (Cria um arquivo webhook_log.txt para você ver o que chega)
$logFile = 'webhook_log.txt';
$input = file_get_contents('php://input');
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $input . "\n\n", FILE_APPEND);

// 1. Segurança: Validação do Token
// O Asaas envia o token no header 'asaas-access-token'
$headers = getallheaders();
$headers = array_change_key_case($headers, CASE_LOWER);
$receivedToken = $headers['asaas-access-token'] ?? $_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? '';

if (!defined('ASAAS_WEBHOOK_TOKEN') || $receivedToken !== ASAAS_WEBHOOK_TOKEN) {
    http_response_code(401);
    echo json_encode(['error' => 'Acesso não autorizado. Token inválido.']);
    exit;
}

$data = json_decode($input, true);

if (!isset($data['event']) || !isset($data['payment'])) {
    echo json_encode(['status' => 'ignored', 'message' => 'Formato desconhecido.']);
    exit;
}

$event = $data['event'];
$payment = $data['payment'];
$paymentId = $payment['id'];

try {
    // 2. Processamento Inteligente
    if ($event === 'PAYMENT_CONFIRMED' || $event === 'PAYMENT_RECEIVED') {
        // A MÁGICA ACONTECE AQUI:
        // 1. Acha o lead com esse ID de pagamento
        // 2. Muda para 'Won' (Venda Feita)
        // 3. Atualiza o status do pagamento para 'PAGO'
        // 4. Adiciona uma nota automática no histórico

        $sql = "UPDATE leads SET 
                funnel_stage = 'Won', 
                payment_status = 'PAGO',
                sales_notes = CONCAT(IFNULL(sales_notes, ''), '\n\n[SISTEMA] ✅ Pagamento Confirmado via Asaas em ', DATE_FORMAT(NOW(), '%d/%m/%Y às %H:%i')),
                last_contact_date = NOW()
                WHERE asaas_payment_id = :pid";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pid' => $paymentId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Venda confirmada e Lead atualizado!']);
        } else {
            echo json_encode(['status' => 'warning', 'message' => 'Pagamento recebido, mas nenhum Lead correspondente encontrado.']);
        }

    } elseif ($event === 'PAYMENT_OVERDUE') {
        // Pagamento Vencido
        $sql = "UPDATE leads SET payment_status = 'VENCIDO' WHERE asaas_payment_id = :pid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pid' => $paymentId]);
        echo json_encode(['status' => 'updated', 'message' => 'Status atualizado para Vencido.']);

    } else {
        // Outros eventos (Criado, Visualizado, etc.)
        echo json_encode(['status' => 'ignored', 'message' => "Evento $event não altera status de funil."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Webhook DB Error: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno no banco de dados.']);
}
?>