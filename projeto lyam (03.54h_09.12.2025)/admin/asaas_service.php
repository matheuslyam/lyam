<?php
// admin/asaas_service.php - Versão 3.0 (Robust Error Handling)
require_once '../config.php';

class AsaasService
{
    private $apiKey;
    private $apiUrl;

    public function __construct()
    {
        if (!defined('ASAAS_API_KEY') || !defined('ASAAS_URL')) {
            throw new Exception("Configuração do Asaas ausente.");
        }
        $this->apiKey = ASAAS_API_KEY;
        $this->apiUrl = ASAAS_URL;
    }

    private function request($endpoint, $method = 'GET', $data = null)
    {
        $ch = curl_init();
        $url = $this->apiUrl . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey,
            'User-Agent: eBikeSolutions-CRM/1.0'
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                $payload = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("Curl Error: " . curl_error($ch));
        }
        curl_close($ch);

        $json = json_decode($response, true);

        // Tratamento de Erros (4xx, 5xx)
        if ($httpCode >= 400) {
            $msg = "Erro desconhecido ($httpCode)";

            // Tenta extrair a mensagem oficial do Asaas
            if (isset($json['errors']) && is_array($json['errors'])) {
                // Pega o primeiro erro da lista
                $firstError = $json['errors'][0];
                $msg = $firstError['description'] ?? $msg;
            } elseif (isset($json['error'])) {
                $msg = $json['error']; // Alguns endpoints retornam assim
            } else {
                // Se não for JSON, mostra o início da resposta bruta (pode ser HTML de erro)
                $msg .= " - Resp: " . substr(strip_tags($response), 0, 100);
            }

            // Loga o erro completo para debug silencioso
            error_log("Asaas Error [$httpCode]: " . print_r($json ?? $response, true));

            throw new Exception($msg);
        }

        return $json;
    }

    public function createCustomer($name, $cpf, $email, $phone)
    {
        // Tenta buscar por CPF primeiro
        try {
            $cpfLimpo = preg_replace('/\D/', '', $cpf);
            $search = $this->request("/customers?cpfCnpj=" . $cpfLimpo);
            if (!empty($search['data']))
                return $search['data'][0]['id'];
        } catch (Exception $e) {
        }

        // Cria novo
        $payload = [
            'name' => $name,
            'cpfCnpj' => $cpf,
            'email' => $email,
            'mobilePhone' => $phone,
            'notificationDisabled' => true // Evita spam do Asaas no email do cliente na criação
        ];
        $res = $this->request('/customers', 'POST', $payload);
        return $res['id'];
    }

    public function createPaymentLink($customerId, $value, $description, $dueDate)
    {
        $payload = [
            'customer' => $customerId,
            'billingType' => 'UNDEFINED',
            'value' => (float) $value,
            'dueDate' => $dueDate,
            'description' => $description,
            'postalService' => false
        ];
        $res = $this->request('/payments', 'POST', $payload);

        return [
            'id' => $res['id'],
            'invoiceUrl' => $res['invoiceUrl'],
            'status' => $res['status']
        ];
    }
}
?>