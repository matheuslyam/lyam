<?php
// ---------------------------------------------------------
// CRM BIKE LEADS - DASHBOARD KANBAN (ARQUIVO MESTRE V2)
// ---------------------------------------------------------

session_start();
require_once '../config.php';

// 1. VERIFICAÇÃO DE SEGURANÇA
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Usuário';
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'seller'; // Pega o cargo da sessão

// 2. DEFINIÇÃO DE ETAPAS DO FUNIL
$funnel_stages = [
    'New Lead' => 'NOVO LEAD',
    'Contact Attempt' => 'TENTATIVA DE CONTATO',
    'Qualified' => 'QUALIFICADO',
    'Negotiation' => 'NEGOCIAÇÃO',
    'Proposal Sent' => 'PROPOSTA ENVIADA',
    'Won/Lost' => 'GANHO/PERDIDO'
];

// 3. INICIALIZAÇÃO DE VARIÁVEIS
$leads_by_stage = [];
foreach ($funnel_stages as $stage => $label) {
    $leads_by_stage[$stage] = [];
}

// 4. BUSCA DE DADOS (Filtra por vendedor se não for admin - Opcional, por enquanto mostra tudo)
try {
    // Futuro: Adicionar WHERE user_id = ... se quiser restringir vendedor
    $stmt = $pdo->query("SELECT * FROM leads ORDER BY score_total DESC, created_at ASC");
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($leads as $lead) {
        $stage = $lead['funnel_stage'] ?? 'New Lead';
        if (!array_key_exists($stage, $leads_by_stage)) {
            $stage = 'New Lead';
        }
        $leads_by_stage[$stage][] = $lead;
    }

} catch (PDOException $e) {
    die("Erro crítico ao carregar leads: " . $e->getMessage());
}

// 5. HELPERS
function format_phone($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone ?? '');
    $length = strlen($phone);
    if ($length === 11)
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7, 4);
    if ($length === 10)
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6, 4);
    return $phone ?: 'Sem número';
}

function display_tags($tags)
{
    if (empty($tags) || $tags === 'Análise Pendente (Erro na IA)') {
        return '<span class="text-[10px] text-yellow-500/80 uppercase tracking-wider font-bold">Análise Pendente</span>';
    }
    $tags_array = explode(',', $tags);
    $html = '<div class="flex flex-wrap gap-1 mt-2">';
    foreach ($tags_array as $tag) {
        $tag = trim($tag);
        if (!empty($tag)) {
            $html .= '<span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-lime-400/10 text-lime-400 border border-lime-400/20 whitespace-nowrap">' . $tag . '</span>';
        }
    }
    $html .= '</div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pipeline | Bike Leads CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-body: #0b1726;
            --bg-column: #162032;
            --bg-card: #1E293B;
            --accent: #a3e635;
        }

        body {
            background-color: var(--bg-body);
            color: #cbd5e1;
            font-family: 'Inter', system-ui, sans-serif;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-body);
        }

        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        .lead-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .lead-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            border-color: var(--accent);
        }
    </style>
</head>

<body class="h-screen flex flex-col overflow-hidden">

    <!-- HEADER -->
    <header class="h-16 bg-[#0f1d30] border-b border-gray-800 flex items-center justify-between px-6 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-lime-400/10 flex items-center justify-center text-lime-400">
                <i class="fas fa-bolt"></i>
            </div>
            <h1 class="text-xl font-bold text-white tracking-tight">Bike<span class="text-lime-400">Leads</span> CRM
            </h1>
        </div>

        <div class="flex items-center gap-6">

            <!-- BOTÃO DE EQUIPE (SÓ PARA ADMIN) -->
            <?php if ($user_role === 'admin'): ?>
                <a href="team.php"
                    class="hidden sm:flex items-center gap-2 text-sm font-bold text-[#a3e635] bg-[#a3e635]/10 px-4 py-2 rounded-lg border border-[#a3e635]/20 hover:bg-[#a3e635]/20 transition">
                    <i class="fas fa-users"></i> Gestão de Equipe
                </a>
            <?php endif; ?>

            <div class="flex items-center gap-4 border-l border-gray-700 pl-6">
                <div class="text-xs text-right hidden sm:block">
                    <p class="text-gray-400 uppercase font-bold text-[10px]"><?= strtoupper($user_role) ?></p>
                    <p class="text-white font-medium"><?= $username ?></p>
                </div>
                <a href="logout.php" class="text-gray-400 hover:text-red-400 transition-colors p-2" title="Sair">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- KANBAN BOARD AREA -->
    <main class="flex-1 overflow-x-auto overflow-y-hidden p-6">
        <div class="flex h-full gap-6 min-w-max">

            <?php foreach ($funnel_stages as $stage_key => $stage_label): ?>
                <!-- COLUNA -->
                <div class="w-80 flex flex-col h-full rounded-xl bg-[#162032] border border-gray-800/50">
                    <div class="p-4 border-b border-gray-700/50 flex justify-between items-center shrink-0">
                        <h2 class="font-bold text-sm text-gray-200 uppercase tracking-wide">
                            <?= $stage_label ?>
                        </h2>
                        <span class="bg-gray-800 text-gray-400 text-xs font-bold px-2 py-1 rounded-full">
                            <?= count($leads_by_stage[$stage_key]) ?>
                        </span>
                    </div>

                    <div id="<?= $stage_key ?>" class="lead-list flex-1 overflow-y-auto p-3 space-y-3 custom-scrollbar">
                        <?php if (empty($leads_by_stage[$stage_key])): ?>
                            <div
                                class="h-24 flex items-center justify-center border-2 border-dashed border-gray-700 rounded-lg opacity-50">
                                <span class="text-xs text-gray-500">Vazio</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($leads_by_stage[$stage_key] as $lead): ?>
                                <div class="lead-card bg-[#1E293B] p-4 rounded-xl border border-gray-700 cursor-grab group relative"
                                    draggable="true" data-lead-id="<?= $lead['id'] ?>">

                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-bold text-white text-sm truncate pr-2"
                                            title="<?= htmlspecialchars($lead['nome']) ?>">
                                            <?= htmlspecialchars($lead['nome']) ?>
                                        </h3>
                                        <div
                                            class="flex items-center gap-1 text-xs font-bold text-lime-400 bg-lime-400/10 px-2 py-0.5 rounded">
                                            <i class="fas fa-star text-[10px]"></i>
                                            <?= $lead['score_total'] ?>
                                        </div>
                                    </div>

                                    <div class="space-y-1.5 mb-3">
                                        <div class="flex items-center gap-2 text-xs text-gray-400">
                                            <i class="fab fa-whatsapp w-4 text-center text-lime-500/80"></i>
                                            <span class="font-mono text-gray-300"><?= format_phone($lead['telefone']) ?></span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-gray-400">
                                            <i class="far fa-envelope w-4 text-center"></i>
                                            <span class="truncate max-w-[180px]" title="<?= htmlspecialchars($lead['email']) ?>">
                                                <?= htmlspecialchars($lead['email']) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <?= display_tags($lead['tags_ai'] ?? '') ?>

                                    <div class="mt-3 pt-3 border-t border-gray-700/50 flex justify-between items-center">
                                        <span class="text-[10px] text-gray-500">
                                            <?= date('d/m H:i', strtotime($lead['created_at'])) ?>
                                        </span>
                                        <a href="details.php?id=<?= $lead['id'] ?>"
                                            class="opacity-0 group-hover:opacity-100 transition-opacity bg-lime-400 hover:bg-lime-500 text-[#0b1726] text-xs font-bold px-3 py-1.5 rounded shadow-lg">
                                            Abrir
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const lists = document.querySelectorAll('.lead-list');
            const cards = document.querySelectorAll('.lead-card');

            cards.forEach(card => {
                card.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('text/plain', e.target.dataset.leadId);
                    e.dataTransfer.effectAllowed = 'move';
                    setTimeout(() => card.classList.add('opacity-50'), 0);
                });
                card.addEventListener('dragend', () => card.classList.remove('opacity-50'));
            });

            lists.forEach(list => {
                list.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    list.classList.add('bg-gray-800/30');
                });
                list.addEventListener('dragleave', () => list.classList.remove('bg-gray-800/30'));
                list.addEventListener('drop', (e) => {
                    e.preventDefault();
                    list.classList.remove('bg-gray-800/30');
                    const leadId = e.dataTransfer.getData('text/plain');
                    const card = document.querySelector(`[data-lead-id="${leadId}"]`);
                    if (card && list.id !== card.parentElement.id) {
                        list.appendChild(card);
                        console.log(`Movendo Lead ${leadId} para ${list.id}...`);
                    }
                });
            });
        });
    </script>
</body>

</html>