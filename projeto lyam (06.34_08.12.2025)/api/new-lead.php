<?php
// ---------------------------------------------------------
// CRM BIKE LEADS - PROCESSAMENTO DE LEAD (ARQUIVO MESTRE)
// ---------------------------------------------------------

// 1. CONFIGURAÇÃO DE CABEÇALHOS E DEPURAÇÃO
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Desativa a exibição de erros no corpo da resposta para não quebrar o JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Função para retornar JSON e encerrar
function jsonResponse($success, $message, $data = [])
{
    // Garante HTTP 200 mesmo em erro, conforme solicitado para debug no frontend
    http_response_code(200);
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

try {
    // 2. CONEXÃO COM O BANCO
    // Ajuste o caminho do config conforme a estrutura de pastas real
    if (file_exists('../config.php')) {
        require_once '../config.php';
    } elseif (file_exists('config.php')) {
        require_once 'config.php';
    } else {
        throw new Exception("Arquivo de configuração não encontrado.");
    }

    // RE-DISABLE ERRORS: Config.php activates them, but we need JSON only.
    ini_set('display_errors', 0);
    error_reporting(E_ALL);

    // 3. RECEBIMENTO DOS DADOS
    $input = file_get_contents("php://input");

    $data = json_decode($input);

    if (empty($data)) {
        // Tenta pegar via $_POST se JSON falhar
        if (!empty($_POST)) {
            $data = (object) $_POST;
        } else {
            throw new Exception("Nenhum dado recebido (Input vazio).");
        }
    }

    // Validação Básica
    if (empty($data->nome) || empty($data->telefone)) {
        throw new Exception("Dados incompletos. Nome e WhatsApp são obrigatórios.");
    }

    // 4. LÓGICA DE SCORE E INTELIGÊNCIA (IA SIMULADA / REGRAS DE NEGÓCIO)
    $score = isset($data->score_total) ? (int) $data->score_total : 0;

    // Fallback de IA
    $tags_ai = [];
    $resumo_ai = "";
    $urgencia = "Baixa";
    $funnel_stage = 'New Lead'; // Padrão

    // Análise Q1 (Missão)
    $missao = $data->q1_missao ?? '';
    if (stripos($missao, 'Trabalho') !== false) {
        $tags_ai[] = "Mobilidade Diária";
    } elseif (stripos($missao, 'Renda') !== false) {
        $tags_ai[] = "Uso Profissional";
        $urgencia = "Alta";
    } else {
        $tags_ai[] = "Lazer";
    }

    // Análise Q2 (Trajeto)
    $trajeto = $data->q2_trajeto ?? '';
    if (stripos($trajeto, 'Subidas') !== false) {
        $tags_ai[] = "Precisa de Torque";
    }

    // Análise Q3 (Prazo)
    $prazo = $data->q3_prazo ?? '';
    if (stripos($prazo, 'Imediatamente') !== false || stripos($prazo, 'Ontem') !== false) {
        $tags_ai[] = "Alta Urgência";
        $urgencia = "Alta";
    } elseif (stripos($prazo, '30 dias') !== false) {
        $urgencia = "Média";
    }

    // Definição de Resumo e Estágio com base no Score
    if ($score >= 30) {
        $resumo_ai = "Lead HOT. Alta compatibilidade e urgência. Indicado para modelos premium com kit de acessórios.";
        $tags_ai[] = "Cliente Hot";
        $funnel_stage = 'Qualified';
    } elseif ($score >= 15) {
        $resumo_ai = "Lead WARM. Precisa de convencimento sobre custo-benefício. Focar em durabilidade e economia.";
        $tags_ai[] = "Em Dúvida";
        $funnel_stage = 'New Lead';
    } else {
        $resumo_ai = "Lead COLD. Curioso ou orçamento limitado. Enviar catálogo de entrada.";
        $tags_ai[] = "Curioso";
        $funnel_stage = 'New Lead';
    }

    $tags_string = implode(', ', $tags_ai);
    $lead_source = 'Quiz Online';

    // 5. INSERÇÃO NO BANCO DE DADOS
    // Colunas atualizadas conforme solicitação
    $sql = "INSERT INTO leads 
            (nome, email, telefone, instagram, q1_missao, q2_trajeto, q3_prazo, score_total, tags_ai, urgencia, resumo, funnel_stage, lead_source, last_contact_date, created_at)
            VALUES 
            (:nome, :email, :telefone, :instagram, :q1, :q2, :q3, :score, :tags, :urgencia, :resumo, :stage, :source, NOW(), NOW())";

    $stmt = $pdo->prepare($sql);

    // Bind dos parâmetros
    $stmt->bindParam(':nome', $data->nome);
    $stmt->bindParam(':email', $data->email);

    // Limpeza do telefone
    $clean_phone = preg_replace('/[^0-9]/', '', $data->telefone);
    $stmt->bindParam(':telefone', $clean_phone);

    $instagram = $data->instagram ?? '';
    $stmt->bindParam(':instagram', $instagram);

    $q1 = $data->q1_missao ?? 'Não informado';
    $stmt->bindParam(':q1', $q1);

    $q2 = $data->q2_trajeto ?? 'Não informado';
    $stmt->bindParam(':q2', $q2);

    $q3 = $data->q3_prazo ?? 'Não informado';
    $stmt->bindParam(':q3', $q3);

    $stmt->bindParam(':score', $score);
    $stmt->bindParam(':tags', $tags_string);
    $stmt->bindParam(':urgencia', $urgencia);
    $stmt->bindParam(':resumo', $resumo_ai);
    $stmt->bindParam(':stage', $funnel_stage);
    $stmt->bindParam(':source', $lead_source);

    if ($stmt->execute()) {
        jsonResponse(true, "Lead cadastrado com sucesso!", ["id" => $pdo->lastInsertId(), "redirect_score" => $score]);
    } else {
        throw new Exception("Falha ao executar inserção no banco.");
    }

} catch (PDOException $e) {
    // Retorna 200 OK com mensagem de erro SQL explícita
    jsonResponse(false, "ERRO SQL: " . $e->getMessage());
} catch (Exception $e) {
    // Retorna 200 OK com mensagem de erro genérica
    jsonResponse(false, "ERRO NO PROCESSAMENTO: " . $e->getMessage());
}
?>