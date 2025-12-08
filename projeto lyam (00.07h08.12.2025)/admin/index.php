<?php
require_once '../config.php';

// --- CONFIGURAÇÃO DO KANBAN ---
// Definição das etapas do funil (Keys = Banco de Dados, Values = Exibição)
$stages = [
    'New Lead' => 'Novo Lead',
    'Qualified' => 'Qualificado',
    'Proposal' => 'Proposta',
    'Negotiation' => 'Negociação',
    'Won/Lost' => 'Ganho/Perdido'
];

// Inicializa o array de leads por estágio para evitar erros de índice indefinido
$leads_by_stage = [];
foreach ($stages as $key => $label) {
    $leads_by_stage[$key] = [];
}

// --- CARREGAMENTO DOS DADOS ---
try {
    // Busca todos os leads ordenados por data de criação (mais recentes primeiro)
    $stmt = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC");
    $allLeads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allLeads as $lead) {
        $stage = $lead['funnel_stage'] ?? 'New Lead';

        // Migração automática: Se o estágio não existir (ex: dados antigos), move para 'New Lead'
        if (!array_key_exists($stage, $leads_by_stage)) {
            $stage = 'New Lead';
        }

        $leads_by_stage[$stage][] = $lead;
    }
} catch (PDOException $e) {
    die("Erro Crítico ao carregar leads: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pipeline CRM | Lyam Bikes</title>
    
    <!-- Dependências Externas (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Configuração Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            base: '#0b1726',
                            card: '#2A3B4D',
                            neon: '#a3e635',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .kanban-col { min-height: 500px; }
        .ghost { opacity: 0.5; background: #2A3B4D; border: 1px dashed #a3e635; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0b1726; }
        ::-webkit-scrollbar-thumb { background: #2A3B4D; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #a3e635; }
    </style>
</head>

<body class="bg-brand-base h-screen flex flex-col overflow-hidden text-white selection:bg-brand-neon selection:text-brand-base">

    <!-- Navbar -->
    <nav class="bg-brand-base/95 border-b border-white/5 px-8 py-5 flex justify-between items-center backdrop-blur-md z-50">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-brand-neon rounded-xl flex items-center justify-center text-brand-base font-bold text-xl shadow-[0_0_15px_rgba(163,230,53,0.4)]">L</div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Lyam <span class="text-brand-neon">CRM</span></h1>
        </div>
        <div class="flex items-center gap-6">
            <span class="text-xs text-slate-500 uppercase font-bold tracking-widest hidden md:block">Pipeline de Vendas</span>
            <span class="text-sm text-slate-400 font-medium bg-brand-card px-3 py-1 rounded-full border border-white/5">Admin</span>
            <a href="logout.php" class="text-sm font-bold text-red-400 hover:text-red-300 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Sair
            </a>
        </div>
    </nav>

    <!-- Kanban Board -->
    <main class="flex-1 overflow-x-auto overflow-y-hidden p-8">
        <div class="flex gap-6 h-full" style="min-width: <?= count($stages) * 320 ?>px;">

            <?php foreach ($stages as $key => $label): ?>
                <div class="flex-1 min-w-[300px] flex flex-col bg-brand-card rounded-3xl border border-white/5 shadow-2xl">
                    
                    <!-- Coluna Header -->
                    <div class="p-6 border-b border-white/5 flex justify-between items-center bg-brand-card/50 rounded-t-3xl backdrop-blur-sm sticky top-0 z-10">
                        <h2 class="font-bold text-slate-200 flex items-center gap-3 text-sm uppercase tracking-wide">
                            <span class="w-2 h-2 rounded-full 
                            <?= $key === 'New Lead' ? 'bg-blue-400 shadow-[0_0_8px_rgba(96,165,250,0.5)]' :
                                ($key === 'Qualified' ? 'bg-brand-neon shadow-[0_0_8px_rgba(163,230,53,0.5)]' :
                                    ($key === 'Won/Lost' ? 'bg-slate-400' : 'bg-yellow-400')) ?>"></span>
                            <?= $label ?>
                        </h2>
                        <span class="bg-brand-base text-slate-400 text-xs font-bold px-3 py-1 rounded-full border border-white/5">
                            <?= count($leads_by_stage[$key]) ?>
                        </span>
                    </div>

                    <!-- Coluna Body (Cards) -->
                    <div id="<?= $key ?>" class="kanban-col flex-1 p-4 space-y-4 overflow-y-auto" data-status="<?= $key ?>">
                        <?php foreach ($leads_by_stage[$key] as $lead): ?>
                            
                            <!-- CARD TEMPLATE INLINE -->
                            <div class="bg-brand-base/40 p-5 rounded-2xl shadow-sm border border-white/5 cursor-move hover:border-brand-neon/50 hover:shadow-[0_0_15px_rgba(163,230,53,0.1)] transition-all group"
                                data-id="<?= $lead['id'] ?>">
                                
                                <div class="flex justify-between items-start mb-3">
                                    <div class="w-2/3">
                                        <h3 class="font-bold text-white text-lg group-hover:text-brand-neon transition-colors truncate">
                                            <?= htmlspecialchars($lead['nome']) ?>
                                        </h3>
                                        <div class="text-xs text-slate-400 mt-1 flex flex-col gap-0.5">
                                            <!-- Telefone Formatado -->
                                            <span class="flex items-center gap-1">
                                                <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                                <?= function_exists('formatPhoneNumber') ? formatPhoneNumber($lead['telefone']) : $lead['telefone'] ?>
                                            </span>
                                            <!-- Email -->
                                            <span class="text-slate-500 truncate flex items-center gap-1">
                                                <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                <?= htmlspecialchars($lead['email']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col items-end gap-1">
                                        <!-- Data Último Contato -->
                                        <?php if (!empty($lead['last_contact_date'])): ?>
                                            <div class="flex items-center gap-1 text-xs text-slate-400" title="Último Contato">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <?= date('d/m', strtotime($lead['last_contact_date'])) ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Score Badge -->
                                        <div class="bg-brand-card border border-white/10 px-2 py-0.5 rounded text-[10px] font-bold text-slate-300">
                                            Score: <span class="<?= $lead['score_total'] >= 30 ? 'text-brand-neon' : ($lead['score_total'] >= 15 ? 'text-yellow-400' : 'text-slate-400') ?>"><?= $lead['score_total'] ?></span>/40
                                        </div>
                                    </div>
                                </div>

                                <!-- Tags AI -->
                                <div class="mb-4">
                                    <div class="flex flex-wrap gap-2">
                                        <?php
                                        $tags = json_decode($lead['tags_ai'] ?? '[]', true);
                                        if (is_array($tags) && !empty($tags)):
                                            foreach (array_slice($tags, 0, 3) as $tag):
                                                ?>
                                                <span class="text-[10px] uppercase font-bold px-2 py-1 rounded bg-brand-neon/10 text-brand-neon border border-brand-neon/20"><?= htmlspecialchars($tag) ?></span>
                                                <?php
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                </div>

                                <!-- Footer Card -->
                                <div class="flex justify-between items-center mt-2 pt-3 border-t border-white/5">
                                    <span class="text-xs text-slate-500 font-medium" title="Criado em"><?= date('d/m H:i', strtotime($lead['created_at'])) ?></span>
                                    <a href="details.php?id=<?= $lead['id'] ?>" class="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1 transition-colors">
                                        Detalhes
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                </div>
                            </div>
                            <!-- FIM CARD TEMPLATE -->

                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </main>

    <!-- Scripts -->
    <script>
        // Inicialização do SortableJS (Drag and Drop)
        const columns = document.querySelectorAll('.kanban-col');
        columns.forEach(col => {
            new Sortable(col, {
                group: 'leads',
                animation: 150,
                ghostClass: 'ghost',
                delay: 100,
                delayOnTouchOnly: true,
                onEnd: function (evt) {
                    const leadId = evt.item.dataset.id;
                    const newStatus = evt.to.dataset.status;

                    // Atualização via AJAX
                    fetch('update_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${leadId}&status=${encodeURIComponent(newStatus)}`
                    })
                    .then(response => {
                        if (!response.ok) console.error('Erro ao atualizar status');
                    })
                    .catch(error => console.error('Erro de rede:', error));
                }
            });
        });
    </script>
</body>
</html>