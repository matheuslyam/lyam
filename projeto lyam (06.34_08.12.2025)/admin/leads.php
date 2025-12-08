<?php
// /admin/leads.php
require_once 'auth_check.php';
require_once '../config.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// LOGICA DE VISÃO
$sql = "SELECT l.*, u.full_name as seller_name 
        FROM leads l 
        LEFT JOIN users u ON l.user_id = u.id";

if ($role !== 'admin') {
    $sql .= " WHERE l.user_id = :uid";
}

$sql .= " ORDER BY l.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    if ($role !== 'admin') {
        $stmt->bindValue(':uid', $user_id);
    }
    $stmt->execute();
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar leads: " . $e->getMessage());
}

// Helper para cores dos Status
function getStatusColor($status)
{
    // Garante que a string status não é nula
    $status = $status ?? 'new';
    return match ($status) {
        'new' => 'text-blue-400 bg-blue-400/10 border-blue-400/20',
        'contacted' => 'text-yellow-400 bg-yellow-400/10 border-yellow-400/20',
        'negotiation' => 'text-orange-400 bg-orange-400/10 border-orange-400/20',
        'won' => 'text-green-400 bg-green-400/10 border-green-400/20',
        'lost' => 'text-red-400 bg-red-400/10 border-red-400/20',
        default => 'text-gray-400',
    };
}

function getStatusLabel($status)
{
    $status = $status ?? 'new';
    return match ($status) {
        'new' => 'Novo',
        'contacted' => 'Contatado',
        'negotiation' => 'Negociação',
        'won' => 'Venda Feita',
        'lost' => 'Perdido',
        default => $status,
    };
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="dark">

<head>
    <meta charset="UTF-8">
    <title>Oportunidades - eBike Solutions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0a0a0a', 800: '#171717', 700: '#262626' },
                        neon: { green: '#4ade80', blue: '#60a5fa' }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-dark-900 text-gray-200 min-h-screen">

    <?php include 'navbar.php'; ?>

    <div class="container mx-auto p-4">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight border-l-4 border-neon-green pl-4">
                    Minhas Oportunidades
                </h1>
                <p class="text-neutral-500 text-sm mt-1 ml-5">Gerencie suas vendas ativas</p>
            </div>
        </div>

        <?php if (count($leads) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($leads as $lead): ?>
                    <div
                        class="bg-dark-800 rounded-xl border border-dark-700 p-5 hover:border-neutral-600 transition-all group relative overflow-hidden">

                        <?php
                        $status_color = getStatusColor($lead['status'] ?? 'new');
                        $status_base_color = '';
                        if (str_contains($status_color, 'blue'))
                            $status_base_color = 'bg-blue-400';
                        elseif (str_contains($status_color, 'green'))
                            $status_base_color = 'bg-green-400';
                        elseif (str_contains($status_color, 'yellow'))
                            $status_base_color = 'bg-yellow-400';
                        elseif (str_contains($status_color, 'orange'))
                            $status_base_color = 'bg-orange-400';
                        elseif (str_contains($status_color, 'red'))
                            $status_base_color = 'bg-red-400';
                        ?>
                        <div class="absolute left-0 top-0 bottom-0 w-1 <?= $status_base_color ?>"></div>

                        <div class="flex justify-between items-start mb-4 pl-3">
                            <div>
                                <h3 class="font-bold text-white text-lg truncate">
                                    <?= htmlspecialchars($lead['name'] ?? 'N/A') ?>
                                </h3>
                                <p class="text-neutral-400 text-xs mt-1">
                                    <?= date('d/m H:i', strtotime($lead['created_at'])) ?>
                                    • <span
                                        class="text-neutral-500 uppercase"><?= htmlspecialchars($lead['source'] ?? 'N/A') ?></span>
                                </p>
                            </div>
                            <span
                                class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded border <?= getStatusColor($lead['status'] ?? 'new') ?>">
                                <?= getStatusLabel($lead['status'] ?? 'new') ?>
                            </span>
                        </div>

                        <div class="pl-3 space-y-3">
                            <div class="bg-dark-900/50 p-3 rounded border border-dark-700/50">
                                <p class="text-xs text-neutral-500 uppercase font-bold">Interesse</p>
                                <p class="text-sm text-gray-300 truncate">
                                    <?= htmlspecialchars($lead['interest'] ?? 'Não informado') ?>
                                </p>

                                <?php if (($lead['value'] ?? 0) > 0): ?>
                                    <div class="mt-2 pt-2 border-t border-dark-700/50 flex justify-between items-center">
                                        <span class="text-xs text-neutral-500 uppercase font-bold">Valor Est.</span>
                                        <span class="text-neon-green font-mono font-bold">R$
                                            <?= number_format($lead['value'] ?? 0, 2, ',', '.') ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex gap-2 mt-4">
                                <a href="https://wa.me/55<?= preg_replace('/\D/', '', $lead['phone'] ?? '') ?>" target="_blank"
                                    class="flex-1 bg-green-600/20 hover:bg-green-600/40 text-green-400 border border-green-600/30 py-2 rounded text-center text-sm font-bold transition-colors flex items-center justify-center gap-2">
                                    <span>WhatsApp</span>
                                </a>
                                <a href="lead_form.php?id=<?= $lead['id'] ?>"
                                    class="px-3 py-2 bg-dark-700 hover:bg-dark-600 border border-dark-600 text-white rounded transition-colors">
                                    ✏️
                                </a>
                            </div>
                        </div>

                        <?php if ($role === 'admin'): ?>
                            <div class="mt-4 pt-3 border-t border-dark-700 pl-3 flex justify-between items-center">
                                <span class="text-[10px] text-neutral-500 uppercase">Responsável</span>
                                <span class="text-xs text-neutral-300 font-medium">
                                    <?= $lead['seller_name'] ? htmlspecialchars($lead['seller_name']) : '<span class="text-red-400">Sem Dono</span>' ?>
                                </span>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-20 opacity-50">
                <div class="mb-4 text-6xl">🚲</div>
                <h3 class="text-xl font-bold text-white">Nenhuma oportunidade encontrada</h3>
                <p class="text-neutral-400">Clique em "+ Novo Lead" na barra de navegação para cadastrar o primeiro cliente.
                </p>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>