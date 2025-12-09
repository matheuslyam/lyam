<?php
// /admin/leads.php
require_once 'auth_check.php';
require_once '../config.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// --- 1. CAPTURA DE FILTROS (GET) ---
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS);
$filter_stage = filter_input(INPUT_GET, 'stage', FILTER_SANITIZE_SPECIAL_CHARS);
$date_start = filter_input(INPUT_GET, 'date_start', FILTER_SANITIZE_SPECIAL_CHARS);
$date_end = filter_input(INPUT_GET, 'date_end', FILTER_SANITIZE_SPECIAL_CHARS);

// --- 2. CONSTRUÇÃO DA QUERY (DYNAMIC SQL) ---
// Base da Query
$sql = "SELECT l.*, u.full_name as seller_name 
        FROM leads l 
        LEFT JOIN users u ON l.user_id = u.id 
        WHERE 1=1"; // Truque para facilitar concatenação de ANDs

$params = [];

// Filtro de Permissão (Seller Blindado)
if ($role !== 'admin') {
    $sql .= " AND l.user_id = :uid";
    $params[':uid'] = $user_id;
}

// Filtro de Busca (Nome, Email, Telefone)
if ($search) {
    $sql .= " AND (l.name LIKE :search OR l.email LIKE :search OR l.phone LIKE :search)";
    $params[':search'] = "%$search%";
}

// Filtro de Estágio
if ($filter_stage && $filter_stage !== 'all') {
    $sql .= " AND l.funnel_stage = :stage";
    $params[':stage'] = $filter_stage;
}

// Filtro de Data (Created At)
if ($date_start) {
    $sql .= " AND DATE(l.created_at) >= :start";
    $params[':start'] = $date_start;
}
if ($date_end) {
    $sql .= " AND DATE(l.created_at) <= :end";
    $params[':end'] = $date_end;
}

// Ordenação Final
$sql .= " ORDER BY l.created_at DESC";

// Execução
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar leads: " . $e->getMessage());
}

// --- HELPERS VISUAIS ---
function getStatusColor($stage)
{
    return match ($stage) {
        'New Lead' => 'text-blue-400 bg-blue-400/10 border-blue-400/20',
        'Contact Attempt' => 'text-yellow-400 bg-yellow-400/10 border-yellow-400/20',
        'Qualified' => 'text-purple-400 bg-purple-400/10 border-purple-400/20',
        'Negotiation' => 'text-orange-400 bg-orange-400/10 border-orange-400/20',
        'Proposal Sent' => 'text-pink-400 bg-pink-400/10 border-pink-400/20',
        'Won' => 'text-green-400 bg-green-400/10 border-green-400/20',
        'Lost' => 'text-red-400 bg-red-400/10 border-red-400/20',
        default => 'text-slate-400 bg-slate-400/10 border-slate-400/20',
    };
}

function getStatusLabel($stage)
{
    return match ($stage) {
        'New Lead' => 'Novo Lead',
        'Contact Attempt' => 'Tentativa',
        'Qualified' => 'Qualificado',
        'Negotiation' => 'Negociação',
        'Proposal Sent' => 'Proposta',
        'Won' => 'Venda Feita',
        'Lost' => 'Perdido',
        default => $stage,
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

        <div class="mb-8">
            <div class="flex flex-col md:flex-row justify-between items-end gap-4 mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight border-l-4 border-neon-green pl-4">
                        Gerenciar Leads
                    </h1>
                    <p class="text-neutral-500 text-sm mt-1 ml-5">
                        <?= count($leads) ?> registro(s) encontrado(s)
                    </p>
                </div>

                <?php if ($search || $filter_stage || $date_start): ?>
                    <a href="leads.php" class="text-xs text-red-400 hover:text-red-300 underline mb-2 md:mb-0">
                        Limpar Filtros ✕
                    </a>
                <?php endif; ?>
            </div>

            <form method="GET" class="bg-dark-800 p-4 rounded-xl border border-dark-700 shadow-lg">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">

                    <div class="md:col-span-4">
                        <label class="block text-xs font-bold text-neutral-500 uppercase mb-1">Buscar</label>
                        <div class="relative">
                            <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>"
                                placeholder="Nome, Telefone ou Email..."
                                class="w-full bg-[#0f172a] border border-dark-700 text-white text-sm rounded-lg pl-10 p-2.5 focus:ring-neon-green focus:border-neon-green outline-none">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-neutral-500 uppercase mb-1">Estágio</label>
                        <select name="stage"
                            class="w-full bg-[#0f172a] border border-dark-700 text-white text-sm rounded-lg p-2.5 focus:ring-neon-green focus:border-neon-green outline-none">
                            <option value="all">Todos os Estágios</option>
                            <option value="New Lead" <?= $filter_stage === 'New Lead' ? 'selected' : '' ?>>Novo Lead
                            </option>
                            <option value="Contact Attempt" <?= $filter_stage === 'Contact Attempt' ? 'selected' : '' ?>>
                                Tentativa</option>
                            <option value="Qualified" <?= $filter_stage === 'Qualified' ? 'selected' : '' ?>>Qualificado
                            </option>
                            <option value="Negotiation" <?= $filter_stage === 'Negotiation' ? 'selected' : '' ?>>Negociação
                            </option>
                            <option value="Won" <?= $filter_stage === 'Won' ? 'selected' : '' ?>>Venda Feita</option>
                            <option value="Lost" <?= $filter_stage === 'Lost' ? 'selected' : '' ?>>Perdido</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-neutral-500 uppercase mb-1">De</label>
                        <input type="date" name="date_start" value="<?= htmlspecialchars($date_start ?? '') ?>"
                            class="w-full bg-[#0f172a] border border-dark-700 text-white text-sm rounded-lg p-2.5 focus:ring-neon-green focus:border-neon-green outline-none [color-scheme:dark]">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-neutral-500 uppercase mb-1">Até</label>
                        <input type="date" name="date_end" value="<?= htmlspecialchars($date_end ?? '') ?>"
                            class="w-full bg-[#0f172a] border border-dark-700 text-white text-sm rounded-lg p-2.5 focus:ring-neon-green focus:border-neon-green outline-none [color-scheme:dark]">
                    </div>

                    <div class="md:col-span-1">
                        <button type="submit"
                            class="w-full bg-neon-green hover:bg-green-500 text-black font-bold rounded-lg text-sm px-4 py-2.5 transition-all">
                            Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (count($leads) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($leads as $lead): ?>
                    <div
                        class="bg-dark-800 rounded-xl border border-dark-700 p-5 hover:border-neutral-600 transition-all group relative overflow-hidden shadow-lg hover:shadow-neon-green/10">

                        <?php
                        $status_color_class = getStatusColor($lead['funnel_stage'] ?? 'New Lead');
                        $border_color = 'bg-gray-500';
                        if (str_contains($status_color_class, 'blue'))
                            $border_color = 'bg-blue-400';
                        elseif (str_contains($status_color_class, 'green'))
                            $border_color = 'bg-green-400';
                        elseif (str_contains($status_color_class, 'yellow'))
                            $border_color = 'bg-yellow-400';
                        elseif (str_contains($status_color_class, 'orange'))
                            $border_color = 'bg-orange-400';
                        elseif (str_contains($status_color_class, 'red'))
                            $border_color = 'bg-red-400';
                        elseif (str_contains($status_color_class, 'purple'))
                            $border_color = 'bg-purple-400';
                        elseif (str_contains($status_color_class, 'pink'))
                            $border_color = 'bg-pink-400';
                        ?>
                        <div class="absolute left-0 top-0 bottom-0 w-1 <?= $border_color ?>"></div>

                        <div class="flex justify-between items-start mb-4 pl-3">
                            <div class="overflow-hidden">
                                <h3 class="font-bold text-white text-lg truncate pr-2"
                                    title="<?= htmlspecialchars($lead['name']) ?>">
                                    <?= htmlspecialchars($lead['name'] ?? 'Sem Nome') ?>
                                </h3>
                                <p class="text-neutral-400 text-xs mt-1 flex items-center gap-1">
                                    <span><?= date('d/m H:i', strtotime($lead['created_at'])) ?></span>
                                    <span class="text-neutral-600">•</span>
                                    <span
                                        class="text-neutral-500 uppercase tracking-wider text-[10px] border border-neutral-700 px-1 rounded">
                                        <?= htmlspecialchars($lead['lead_source'] ?? 'Site') ?>
                                    </span>
                                </p>
                            </div>
                            <span
                                class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded border whitespace-nowrap <?= $status_color_class ?>">
                                <?= getStatusLabel($lead['funnel_stage']) ?>
                            </span>
                        </div>

                        <div class="pl-3 space-y-3">
                            <div class="bg-dark-900/50 p-3 rounded border border-dark-700/50">
                                <div class="flex justify-between items-start mb-1">
                                    <p class="text-[10px] text-neutral-500 uppercase font-bold">Resumo / Interesse</p>
                                    <?php if (($lead['score_total'] ?? 0) > 0): ?>
                                        <span
                                            class="text-[10px] font-bold text-neon-green bg-neon-green/10 px-1.5 py-0.5 rounded border border-neon-green/20">
                                            Score: <?= $lead['score_total'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-300 line-clamp-2"
                                    title="<?= htmlspecialchars($lead['summary'] ?? '') ?>">
                                    <?= htmlspecialchars($lead['summary'] ?? 'Não informado') ?>
                                </p>
                            </div>

                            <div class="flex gap-2 mt-4 pt-2 border-t border-dark-700/50">
                                <a href="https://wa.me/55<?= preg_replace('/\D/', '', $lead['phone'] ?? '') ?>" target="_blank"
                                    class="flex-1 bg-green-600/10 hover:bg-green-600/20 text-green-400 border border-green-600/30 py-2 rounded-lg text-center text-sm font-bold transition-all flex items-center justify-center gap-2 hover:shadow-[0_0_10px_rgba(34,197,94,0.2)]">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.017-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                                    </svg>
                                    WhatsApp
                                </a>
                                <a href="details.php?id=<?= $lead['id'] ?>"
                                    class="px-4 py-2 bg-dark-700 hover:bg-dark-600 border border-dark-600 text-white rounded-lg transition-colors flex items-center justify-center group-hover:border-neon-green/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                </a>
                                <a href="lead_form.php?id=<?= $lead['id'] ?>"
                                    class="px-4 py-2 bg-dark-700 hover:bg-dark-600 border border-dark-600 text-white rounded-lg transition-colors flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <?php if ($role === 'admin'): ?>
                            <div class="mt-4 pt-3 border-t border-dark-700 pl-3 flex justify-between items-center">
                                <span class="text-[10px] text-neutral-500 uppercase tracking-widest">Responsável</span>
                                <span class="text-xs text-neutral-300 font-bold">
                                    <?= $lead['seller_name'] ? htmlspecialchars($lead['seller_name']) : '<span class="text-red-400 italic">-- Sem Dono --</span>' ?>
                                </span>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center py-20 opacity-50">
                <div
                    class="w-20 h-20 bg-dark-800 rounded-full flex items-center justify-center mb-4 border border-dark-700">
                    <span class="text-4xl">🔍</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Nenhum resultado</h3>
                <p class="text-neutral-400 text-center max-w-xs">Não encontramos leads com esses filtros. Tente limpar a
                    busca.</p>
                <a href="leads.php" class="mt-4 text-neon-green hover:underline">Limpar Filtros</a>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>