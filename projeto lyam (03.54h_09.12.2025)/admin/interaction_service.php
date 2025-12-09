<?php
// admin/interaction_service.php
require_once '../config.php';

class InteractionService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Registra uma interação
    public function log($leadId, $type, $content, $userId = null)
    {
        try {
            $sql = "INSERT INTO interactions (lead_id, user_id, type, content, created_at) 
                    VALUES (:lid, :uid, :type, :content, NOW())";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':lid' => $leadId,
                ':uid' => $userId, // Se null, será gravado como NULL (Sistema)
                ':type' => $type,
                ':content' => $content
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao logar interação: " . $e->getMessage());
            return false;
        }
    }

    // Busca o histórico completo de um lead
    public function getHistory($leadId)
    {
        $sql = "SELECT i.*, u.full_name as author_name, u.role 
                FROM interactions i 
                LEFT JOIN users u ON i.user_id = u.id 
                WHERE i.lead_id = :lid 
                ORDER BY i.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':lid' => $leadId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>