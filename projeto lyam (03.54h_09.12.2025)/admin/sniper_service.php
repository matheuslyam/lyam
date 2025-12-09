<?php
// admin/sniper_service.php - O Distribuidor de Leads por Mérito
require_once '../config.php';

class SniperService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Define quem merece receber o lead
    public function getTargetSeller($score)
    {
        // 1. Busca todos os vendedores ATIVOS
        $stmt = $this->pdo->prepare("SELECT id, full_name FROM users WHERE role = 'seller' AND active = 1");
        $stmt->execute();
        $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fallback: Se não houver vendedores, manda para o Admin principal
        if (empty($sellers)) {
            $stmt = $this->pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
            return $stmt->fetchColumn() ?: null;
        }

        // 2. Calcula Performance (Vendas 'Won' nos últimos 7 dias)
        $performance = [];
        foreach ($sellers as $seller) {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM leads 
                WHERE user_id = ? 
                AND funnel_stage = 'Won' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([$seller['id']]);
            $sales = $stmt->fetchColumn();

            // Armazena ID => Vendas
            $performance[$seller['id']] = $sales;
        }

        // Ordena do maior para o menor (Mantendo a associação ID => Vendas)
        arsort($performance);

        $bestSellerId = array_key_first($performance); // O Lobo
        $worstSellerId = array_key_last($performance); // O Estagiário

        // 3. A Decisão do Sniper 🎯
        if ($score >= 30) {
            // Lead HOT: Vai para quem converte mais (Mérito)
            return $bestSellerId;
        } else {
            // Lead WARM/COLD: Vai para quem vende menos (Treino / Carpir Lote)
            // Obs: Em times grandes, aqui poderíamos fazer um Round Robin entre os piores
            return $worstSellerId;
        }
    }
}
?>