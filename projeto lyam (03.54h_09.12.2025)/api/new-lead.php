<?php
// CRM EBIKE SOLUTIONS - API DE ENTRADA (V9 - SNIPER ATIVO 🎯)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config.php';

// Carrega Serviços
if (file_exists('../admin/interaction_service.php'))
    require_once '../admin/interaction_service.php';
if (file_exists('../admin/sniper_service.php'))
    require_once '../admin/sniper_service.php';

function jsonResponse($success, $message, $data = [])
{
    http_response_code(200);
    echo json_encode(["success" => $success, "message" => $message, "data" => $data]);
    exit;
}

try {
    if (!isset($pdo))
        throw new Exception("Erro de conexão com o banco.");

    $input = file_get_contents("php://input");
    $data = json_decode($input);
    if (empty($data))
        $data = (object) $_POST;

    // 1. Validação
    if (empty($data->name))
        throw new Exception("Nome é obrigatório.");
    $clean_phone = preg_replace('/[^0-9]/', '', $data->phone ?? '');
    if (strlen($clean_phone) < 10)
        throw new Exception("WhatsApp inválido.");

    // 2. Anti-Duplicidade
    $stmtCheck = $pdo->prepare("SELECT id FROM leads WHERE phone = :phone LIMIT 1");
    $stmtCheck->execute([':phone' => $clean_phone]);
    if ($stmtCheck->fetch()) {
        jsonResponse(true, "Lead duplicado ignorado.", ["is_duplicate" => true]);
    }

    // 3. Processamento Score & IA
    $score = isset($data->score_total) ? (int) $data->score_total : 0;
    $tags_ai = [];
    $urgency = "Low";

    // Análise Rápida
    $mission = $data->quiz_mission ?? '';
    if (stripos($mission, 'Trabalho') !== false)
        $tags_ai[] = "Daily Commute";
    elseif (stripos($mission, 'Renda') !== false) {
        $tags_ai[] = "Professional Use";
        $urgency = "High";
    } else
        $tags_ai[] = "Leisure";

    if (stripos($data->quiz_path ?? '', 'Subidas') !== false)
        $tags_ai[] = "High Torque Needed";

    $timeline = $data->quiz_timeline ?? '';
    if (stripos($timeline, 'Imediatamente') !== false) {
        $tags_ai[] = "High Urgency";
        $urgency = "High";
    }

    // Classificação
    if ($score >= 30) {
        $funnel_stage = 'Qualified'; // Hot
        $summary_ai = "HOT Lead ($score pts). Urgência detectada.";
        $tags_ai[] = "Hot Lead";
    } elseif ($score >= 15) {
        $funnel_stage = 'New Lead'; // Warm
        $summary_ai = "WARM Lead ($score pts). Precisa de nutrição.";
        $tags_ai[] = "Warm Lead";
    } else {
        $funnel_stage = 'New Lead'; // Cold
        $summary_ai = "COLD Lead ($score pts). Curioso.";
        $tags_ai[] = "Cold Lead";
    }

    // 4. ATIVAÇÃO DO SNIPER 🎯
    $sniper = new SniperService($pdo);
    $targetSellerId = $sniper->getTargetSeller($score);

    // 5. Inserção (Com user_id preenchido)
    $sql = "INSERT INTO leads 
            (name, email, phone, instagram, 
             quiz_mission, quiz_path, quiz_timeline, 
             score_total, tags_ai, urgency, summary, 
             funnel_stage, lead_source, user_id, last_contact_date, created_at,
             utm_source, utm_medium, utm_campaign, utm_content, utm_term)
            VALUES 
            (:name, :email, :phone, :insta, 
             :q1, :q2, :q3, 
             :score, :tags, :urg, :res, 
             :stage, :source, :uid, NOW(), NOW(),
             :us, :um, :uc, :uct, :ut)";

    $stmt = $pdo->prepare($sql);

    // Binds
    $stmt->bindValue(':name', $data->name);
    $stmt->bindValue(':email', $data->email ?? '');
    $stmt->bindValue(':phone', $clean_phone);
    $stmt->bindValue(':insta', $data->instagram ?? '');
    $stmt->bindValue(':q1', $data->quiz_mission ?? '');
    $stmt->bindValue(':q2', $data->quiz_path ?? '');
    $stmt->bindValue(':q3', $data->quiz_timeline ?? '');
    $stmt->bindValue(':score', $score);
    $stmt->bindValue(':tags', json_encode($tags_ai));
    $stmt->bindValue(':urg', $urgency);
    $stmt->bindValue(':res', $summary_ai);
    $stmt->bindValue(':stage', $funnel_stage);
    $stmt->bindValue(':source', (!empty($data->utm_source) && $data->utm_source !== 'organic') ? $data->utm_source : 'Quiz/Site');
    $stmt->bindValue(':uid', $targetSellerId); // O Sniper decidiu!

    // UTMs
    $stmt->bindValue(':us', $data->utm_source ?? null);
    $stmt->bindValue(':um', $data->utm_medium ?? null);
    $stmt->bindValue(':uc', $data->utm_campaign ?? null);
    $stmt->bindValue(':uct', $data->utm_content ?? null);
    $stmt->bindValue(':ut', $data->utm_term ?? null);

    if ($stmt->execute()) {
        $newLeadId = $pdo->lastInsertId();

        // Log na Timeline
        if (class_exists('InteractionService')) {
            $interaction = new InteractionService($pdo);

            // Busca nome do vendedor para o log
            $sellerName = "Ninguém";
            if ($targetSellerId) {
                $stmtSeller = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                $stmtSeller->execute([$targetSellerId]);
                $sellerName = $stmtSeller->fetchColumn();
            }

            $logContent = "Lead capturado via Quiz.\nScore: $score pts.\n🎯 Sniper atribuiu para: $sellerName";
            $interaction->log($newLeadId, 'system', $logContent, null);
        }

        jsonResponse(true, "Lead cadastrado e distribuído!", ["id" => $newLeadId]);
    } else {
        throw new Exception("Falha no banco.");
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    jsonResponse(false, "Erro: " . $e->getMessage());
}
?>