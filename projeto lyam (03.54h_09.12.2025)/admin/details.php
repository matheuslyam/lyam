<?php
// /admin/details.php - V6 (Design Premium Restore + Tasks)
require_once 'auth_check.php';
require_once '../config.php';
require_once 'asaas_service.php';
require_once 'interaction_service.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: leads.php");
    exit;
}

$interactionService = new InteractionService($pdo);
$asaasService = new AsaasService();

$stmt = $pdo->prepare("SELECT * FROM leads WHERE id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();
$lead = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lead)
    die("Lead não encontrado.");
if ($_SESSION['role'] !== 'admin' && ($lead['user_id'] ?? 0) !== $_SESSION['user_id'])
    die("Acesso Negado.");

$msg = '';
$error_msg = '';
$userId = $_SESSION['user_id'];

// --- PROCESSAMENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. AGENDAR FOLLOW-UP
    if (isset($_POST['action']) && $_POST['action'] === 'schedule_followup') {
        $date = $_POST['followup_date'];
        $type = $_POST['followup_type'];
        $note = filter_input(INPUT_POST, 'followup_note', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($date) {
            $sql = "UPDATE leads SET next_followup_date = :dt, next_followup_type = :tp, next_followup_note = :nt WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':dt' => $date, ':tp' => $type, ':nt' => $note, ':id' => $id]);

            $logMsg = "📅 Agendou: " . ucfirst($type) . " para " . date('d/m H:i', strtotime($date));
            if ($note)
                $logMsg .= " - $note";
            $interactionService->log($id, 'system', $logMsg, $userId);

            header("Location: details.php?id=$id&msg=scheduled");
            exit;
        }
    }

    // 2. CONCLUIR TAREFA
    if (isset($_POST['action']) && $_POST['action'] === 'complete_followup') {
        $sql = "UPDATE leads SET next_followup_date = NULL, next_followup_type = NULL, next_followup_note = NULL WHERE id = :id";
        $pdo->prepare($sql)->execute([':id' => $id]);
        $interactionService->log($id, 'system', "✅ Tarefa concluída.", $userId);
        header("Location: details.php?id=$id&msg=task_done");
        exit;
    }

    // 3. ADD NOTA
    if (isset($_POST['action']) && $_POST['action'] === 'add_note') {
        $content = filter_input(INPUT_POST, 'note_content', FILTER_SANITIZE_SPECIAL_CHARS);
        if ($content) {
            $interactionService->log($id, 'note', $content, $userId);
            header("Location: details.php?id=$id&msg=note_added");
            exit;
        }
    }

    // 4. REGISTRAR CONTATO
    if (isset($_POST['action']) && $_POST['action'] === 'register_contact') {
        $upd = $pdo->prepare("UPDATE leads SET last_contact_date = NOW() WHERE id = :id");
        $upd->execute([':id' => $id]);
        $interactionService->log($id, 'whatsapp', "Tentativa de contato registrada.", $userId);
        header("Location: details.php?id=$id&msg=contacted");
        exit;
    }

    // 5. GERAR LINK ASAAS
    if (isset($_POST['action']) && $_POST['action'] === 'generate_payment') {
        try {
            $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
            $valueRaw = $_POST['value'] ?? '0';
            $value = str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9,.]/', '', $valueRaw));
            $dueDate = $_POST['due_date'];

            if (strlen($cpf) < 11)
                throw new Exception("CPF inválido.");
            if ((float) $value <= 0)
                throw new Exception("Valor inválido.");

            $customerId = $lead['asaas_customer_id'] ?? null;
            if (empty($customerId)) {
                $customerId = $asaasService->createCustomer($lead['name'], $cpf, $lead['email'] ?? 'mail@nao.com', $lead['phone']);
                try {
                    $pdo->prepare("UPDATE leads SET asaas_customer_id = ? WHERE id = ?")->execute([$customerId, $id]);
                } catch (Exception $e) {
                }
            }
            $payment = $asaasService->createPaymentLink($customerId, $value, "Pedido #$id", $dueDate);

            $pdo->prepare("UPDATE leads SET asaas_payment_id=?, payment_link=?, payment_status=?, payment_due_date=?, funnel_stage='Negotiation' WHERE id=?")
                ->execute([$payment['id'], $payment['invoiceUrl'], $payment['status'], $dueDate, $id]);

            $interactionService->log($id, 'payment', "Gerou Link Asaas: R$ " . number_format($value, 2, ',', '.'), $userId);
            header("Location: details.php?id=$id&msg=payment_created");
            exit;
        } catch (Exception $e) {
            $error_msg = "Erro Asaas: " . $e->getMessage();
        }
    }
}

$history = $interactionService->getHistory($id);
?>
<!DOCTYPE html>
<html lang="pt-br" class="dark">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($lead['name']) ?> | Detalhes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0a0a0a', 800: '#171717', 700: '#262626' },
                        neon: { green: '#4ade80', blue: '#60a5fa', yellow: '#facc15', red: '#f87171' }
                    },
                    boxShadow: {
                        'neon-green': '0 0 20px rgba(74, 222, 128, 0.15)',
                        'neon-blue': '0 0 20px rgba(96, 165, 250, 0.15)',
                        'card-depth': '0 10px 30px -10px rgba(0, 0, 0, 0.5)'
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-dark-900 text-gray-200 min-h-screen p-6 font-sans selection:bg-neon-green selection:text-black"
    x-data="{ showCalc: false, showSchedule: false }">

    <div class="max-w-7xl mx-auto lg:p-10">

        <div
            class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 border-b border-dark-700 pb-8">
            <div>
                <a href="leads.php"
                    class="text-neon-green text-xs font-bold hover:underline mb-3 block tracking-widest uppercase flex items-center gap-2">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar para Lista
                </a>
                <div class="flex items-center gap-4">
                    <h1 class="text-4xl lg:text-5xl font-bold text-white tracking-tight">
                        <?= htmlspecialchars($lead['name']) ?></h1>
                    <?= getStageBadge($lead['funnel_stage']) ?>
                </div>
                <div class="mt-3 flex items-center gap-4 text-sm text-neutral-500">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                        <?= date('d/m/Y \à\s H:i', strtotime($lead['created_at'])) ?>
                    </span>
                    <span class="h-1 w-1 rounded-full bg-dark-700"></span>
                    <span
                        class="uppercase tracking-wider font-bold text-xs bg-dark-800 border border-dark-700 px-2 py-0.5 rounded text-neutral-400"><?= htmlspecialchars($lead['lead_source'] ?? 'Site') ?></span>
                </div>
            </div>

            <div class="flex gap-3">
                <a href="lead_form.php?id=<?= $lead['id'] ?>"
                    class="px-6 py-3 bg-dark-800 hover:bg-dark-700 border border-dark-700 text-white rounded-xl text-sm font-bold shadow-lg transition-transform hover:-translate-y-0.5">Editar</a>
                <a href="https://wa.me/55<?= preg_replace('/\D/', '', $lead['phone']) ?>" target="_blank"
                    class="px-6 py-3 bg-green-600 hover:bg-green-500 text-white rounded-xl text-sm font-bold shadow-[0_0_20px_rgba(34,197,94,0.3)] flex items-center gap-2 transition-transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.017-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                    </svg> WhatsApp
                </a>
            </div>
        </div>

        <?php if ($msg): ?>
            <div
                class="bg-green-500/10 border border-green-500/20 text-green-400 p-4 rounded-xl mb-6 font-bold flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg> Ação realizada com sucesso!</div><?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-200 p-4 rounded-xl mb-6 font-mono text-sm">
                <?= $error_msg ?></div><?php endif; ?>

        <div class="mb-10">
            <?php if (!empty($lead['next_followup_date'])): ?>
                <div
                    class="bg-gradient-to-r from-neon-yellow/10 to-transparent border border-neon-yellow/30 p-6 rounded-2xl flex flex-col md:flex-row justify-between items-center gap-6 shadow-neon-green">
                    <div class="flex items-center gap-5">
                        <div class="bg-neon-yellow/20 p-4 rounded-full text-neon-yellow animate-pulse">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-neon-yellow font-bold uppercase text-xs tracking-widest mb-1">Próximo Passo</h4>
                            <p class="text-white text-2xl font-bold tracking-tight">
                                <?= ucfirst($lead['next_followup_type']) ?> em
                                <?= date('d/m \à\s H:i', strtotime($lead['next_followup_date'])) ?>
                            </p>
                            <?php if ($lead['next_followup_note']): ?>
                                <p class="text-sm text-neutral-400 mt-1 italic">
                                    "<?= htmlspecialchars($lead['next_followup_note']) ?>"</p><?php endif; ?>
                        </div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="complete_followup">
                        <button type="submit"
                            class="px-8 py-3 bg-neon-yellow hover:bg-yellow-400 text-black font-bold rounded-xl shadow-lg shadow-yellow-500/20 transition-transform hover:-translate-y-0.5 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            Concluir
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div
                    class="bg-red-500/5 border border-red-500/20 p-6 rounded-2xl flex flex-col md:flex-row justify-between items-center gap-6 group hover:border-red-500/40 transition-colors">
                    <div class="flex items-center gap-5">
                        <div class="bg-red-500/20 p-3 rounded-full text-red-500">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-red-400 font-bold uppercase text-xs tracking-widest mb-1">Atenção Necessária
                            </h4>
                            <p class="text-white font-bold text-lg">Lead sem próximo passo definido.</p>
                            <p class="text-sm text-neutral-500">Agende uma tarefa para não perder o timing da venda.</p>
                        </div>
                    </div>
                    <button @click="showSchedule = true"
                        class="px-8 py-3 bg-dark-800 hover:bg-dark-700 text-white border border-dark-600 rounded-xl font-bold transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                        <span>📅 Agendar Agora</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-8">
                <div
                    class="bg-dark-800 p-8 rounded-2xl border border-dark-700 shadow-card-depth relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-6 opacity-5 pointer-events-none"><svg
                            class="w-32 h-32 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg></div>
                    <h3
                        class="text-neon-green font-bold uppercase text-xs tracking-widest mb-6 pb-2 border-b border-dark-700">
                        Dados do Lead</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 relative z-10">
                        <div class="bg-dark-900/50 p-4 rounded-xl border border-dark-700/50"><span
                                class="text-[10px] text-neutral-500 uppercase font-bold tracking-wider block mb-1">Telefone</span><span
                                class="text-xl text-white font-mono tracking-tight"><?= formatPhoneNumber($lead['phone']) ?></span>
                        </div>
                        <div class="bg-dark-900/50 p-4 rounded-xl border border-dark-700/50"><span
                                class="text-[10px] text-neutral-500 uppercase font-bold tracking-wider block mb-1">Email</span><span
                                class="text-base text-gray-300 truncate"><?= htmlspecialchars($lead['email'] ?: '---') ?></span>
                        </div>
                        <div class="col-span-1 md:col-span-2"><span
                                class="text-[10px] text-neutral-500 uppercase font-bold tracking-wider block mb-2">Interesse</span>
                            <div
                                class="bg-dark-900/30 p-4 rounded-xl border border-dark-700/30 text-gray-300 leading-relaxed text-sm">
                                <?= nl2br(htmlspecialchars($lead['summary'] ?: 'Sem resumo.')) ?></div>
                        </div>
                    </div>
                    <div
                        class="bg-dark-900 p-5 rounded-xl border border-dark-700 flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-4">
                            <div class="bg-neon-green/10 p-3 rounded-lg text-neon-green"><svg class="w-6 h-6"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                    </path>
                                </svg></div>
                            <div><span class="text-[10px] text-neutral-500 uppercase font-bold block">Valor
                                    Estimado</span><span class="text-2xl font-bold text-white tracking-tight">R$
                                    <?= number_format($lead['value'] ?? 0, 2, ',', '.') ?></span></div>
                        </div>
                        <?php if (!empty($lead['score_total'])): ?>
                            <div class="flex items-center gap-4 border-l border-dark-700 pl-6">
                                <div class="text-right"><span
                                        class="text-[10px] text-neutral-500 uppercase font-bold block">Score</span><span
                                        class="text-2xl font-bold text-neon-green"><?= $lead['score_total'] ?></span></div>
                            </div><?php endif; ?>
                    </div>
                </div>

                <div class="bg-dark-800 p-8 rounded-2xl border border-dark-700 shadow-card-depth">
                    <h3 class="text-white font-bold uppercase text-xs tracking-widest mb-8 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg> Timeline
                    </h3>
                    <?php if (empty($history)): ?>
                        <div class="text-center py-10 opacity-30 text-sm">Nenhuma interação registrada.</div>
                    <?php else: ?>
                        <div
                            class="space-y-8 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-dark-700 before:to-transparent">
                            <?php foreach ($history as $item):
                                $isSystem = empty($item['user_id']);
                                $iconColor = match ($item['type']) { 'payment', 'won' => 'bg-green-500', 'whatsapp', 'contact' => 'bg-blue-500', 'system' => 'bg-purple-500', default => 'bg-dark-600 border border-dark-500'};
                                ?>
                                <div
                                    class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 rounded-full border border-dark-900 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 <?= $iconColor ?> text-white z-10">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </div>
                                    <div
                                        class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-xl border border-dark-700 bg-dark-900 shadow-sm">
                                        <div class="flex items-center justify-between mb-1">
                                            <span
                                                class="font-bold text-slate-200 text-xs"><?= $isSystem ? '🤖 Sistema' : htmlspecialchars($item['author_name']) ?></span>
                                            <time
                                                class="font-mono text-[10px] text-slate-500"><?= date('d/m H:i', strtotime($item['created_at'])) ?></time>
                                        </div>
                                        <p class="text-slate-400 text-sm leading-snug">
                                            <?= nl2br(htmlspecialchars($item['content'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="space-y-8">
                <div
                    class="bg-dark-800 p-6 rounded-2xl border border-blue-500/30 shadow-neon-blue relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/20 rounded-full blur-3xl"></div>
                    <div class="flex justify-between items-center mb-6">
                        <h3
                            class="text-blue-400 font-bold uppercase text-xs tracking-widest flex items-center gap-2 relative z-10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg> Financeiro
                        </h3>
                        <?php if (empty($lead['payment_link'])): ?><button @click="showCalc = true"
                                class="text-[10px] bg-blue-500/20 text-blue-300 px-3 py-1 rounded hover:bg-blue-500/30 border border-blue-500/30 font-bold">🧮
                                CALC</button><?php endif; ?>
                    </div>
                    <?php if (!empty($lead['payment_link'])): ?>
                        <div class="text-center space-y-4 relative z-10">
                            <div class="bg-blue-500/10 border border-blue-500/20 p-4 rounded-xl">
                                <p class="text-[10px] text-blue-300 uppercase font-bold mb-1">Status</p>
                                <p class="text-2xl font-bold text-white tracking-tight"><?= $lead['payment_status'] ?></p>
                                <p class="text-xs text-neutral-400 mt-2">Vence:
                                    <?= date('d/m/Y', strtotime($lead['payment_due_date'])) ?></p>
                            </div>
                            <a href="<?= $lead['payment_link'] ?>" target="_blank"
                                class="block w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 rounded-xl text-center shadow-lg transition-transform hover:-translate-y-0.5">🔗
                                Ver Fatura</a>
                            <button
                                onclick="navigator.clipboard.writeText('<?= $lead['payment_link'] ?>'); alert('Copiado!')"
                                class="text-xs text-neutral-400 hover:text-white underline decoration-dashed">Copiar
                                Link</button>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="space-y-4 relative z-10">
                            <input type="hidden" name="action" value="generate_payment">
                            <input type="text" name="value" id="paymentValue"
                                value="<?= number_format($lead['value'] ?? 0, 2, ',', '.') ?>"
                                class="w-full bg-dark-900 border-dark-700 rounded-lg p-3 text-white font-mono focus:border-blue-500 outline-none"
                                placeholder="R$ 0,00">
                            <input type="text" name="cpf" placeholder="CPF/CNPJ" required
                                class="w-full bg-dark-900 border-dark-700 rounded-lg p-3 text-white focus:border-blue-500 outline-none">
                            <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required
                                class="w-full bg-dark-900 border-dark-700 rounded-lg p-3 text-white focus:border-blue-500 outline-none [color-scheme:dark]">
                            <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-lg shadow-lg transition-transform hover:-translate-y-0.5">Gerar
                                Link</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="bg-dark-800 p-6 rounded-2xl border border-dark-700">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_note">
                        <label class="block text-[10px] font-bold text-neutral-500 uppercase mb-2">Nota Rápida</label>
                        <textarea name="note_content" rows="3"
                            class="w-full bg-dark-900 border border-dark-700 rounded-xl p-3 text-white text-sm focus:border-neon-green outline-none resize-none mb-3"
                            placeholder="Digite algo..."></textarea>
                        <button type="submit"
                            class="w-full bg-dark-700 hover:bg-dark-600 text-white font-bold py-2 rounded-lg text-sm">Adicionar
                            à Timeline</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showSchedule" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm"
        x-cloak x-transition>
        <div class="bg-dark-800 p-8 rounded-2xl border border-neon-yellow/30 w-full max-w-md shadow-2xl relative"
            @click.away="showSchedule = false">
            <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2"><span
                    class="text-neon-yellow">📅</span> Agendar</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="schedule_followup">
                <div><label class="block text-xs font-bold text-neutral-500 uppercase mb-1">Quando?</label><input
                        type="datetime-local" name="followup_date" required
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg p-3 text-white focus:border-neon-yellow outline-none [color-scheme:dark]">
                </div>
                <div><label class="block text-xs font-bold text-neutral-500 uppercase mb-1">O que?</label><select
                        name="followup_type"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg p-3 text-white focus:border-neon-yellow outline-none">
                        <option value="whatsapp">Enviar WhatsApp</option>
                        <option value="ligacao">Ligar</option>
                        <option value="email">Email</option>
                    </select></div>
                <div><label class="block text-xs font-bold text-neutral-500 uppercase mb-1">Obs</label><textarea
                        name="followup_note" rows="2"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg p-3 text-white text-sm focus:border-neon-yellow outline-none"></textarea>
                </div>
                <button type="submit"
                    class="w-full bg-neon-yellow hover:bg-yellow-400 text-black font-bold py-3 rounded-xl mt-2">Salvar</button>
            </form>
            <button @click="showSchedule = false"
                class="absolute top-4 right-4 text-neutral-500 hover:text-white">✕</button>
        </div>
    </div>

    <div x-show="showCalc" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm"
        x-cloak x-transition>
        <div class="bg-dark-800 p-8 rounded-2xl border border-blue-500/30 w-full max-w-md shadow-2xl relative"
            @click.away="showCalc = false">
            <h3 class="text-xl font-bold text-white mb-6"><span>🧮</span> Calculadora</h3>
            <div class="space-y-4">
                <div><label class="block text-xs font-bold text-blue-400 uppercase mb-1">Receber LIMPO:</label><input
                        type="number" id="calcDesired" placeholder="Ex: 5000"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg p-3 text-white focus:border-blue-500 outline-none text-lg">
                </div>
                <div><label class="block text-xs font-bold text-neutral-500 uppercase mb-1">Método</label><select
                        id="calcMethod"
                        class="w-full bg-dark-900 border border-dark-700 rounded-lg p-3 text-white focus:border-blue-500 outline-none">
                        <option value="pix">Pix</option>
                        <option value="credit_1x">Crédito 1x</option>
                        <option value="credit_2x">Crédito 2x-6x</option>
                        <option value="credit_7x">Crédito 7x-12x</option>
                    </select></div>
                <div class="bg-blue-900/20 p-4 rounded-xl border border-blue-500/20 text-center mt-4">
                    <p class="text-xs text-blue-300 uppercase mb-1">Cobrar:</p>
                    <p class="text-3xl font-bold text-white" id="calcResult">R$ 0,00</p>
                </div>
                <button onclick="applyCalculation()"
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl mt-2">Aplicar</button>
            </div>
            <button @click="showCalc = false"
                class="absolute top-4 right-4 text-neutral-500 hover:text-white">✕</button>
        </div>
    </div>

    <script>
        const RATES = { pix: { percent: 0, fixed: 1.99 }, credit_1x: { percent: 2.99, fixed: 0.49 }, credit_2x: { percent: 3.49, fixed: 0.49 }, credit_7x: { percent: 3.99, fixed: 0.49 } };
        const desiredInput = document.getElementById('calcDesired');
        const methodSelect = document.getElementById('calcMethod');
        const resultDisplay = document.getElementById('calcResult');
        let calculatedValue = 0;
        function calculate() {
            const desired = parseFloat(desiredInput.value) || 0;
            const rate = RATES[methodSelect.value];
            if (desired > 0) { calculatedValue = (desired + rate.fixed) / (1 - (rate.percent / 100)); resultDisplay.innerText = calculatedValue.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL', minimumFractionDigits: 2 }); } else { resultDisplay.innerText = 'R$ 0,00'; }
        }
        function applyCalculation() { if (calculatedValue > 0) { document.getElementById('paymentValue').value = calculatedValue.toLocaleString('pt-BR', { minimumFractionDigits: 2 }); document.querySelector('[x-data]').__x.$data.showCalc = false; } }
        desiredInput.addEventListener('input', calculate); methodSelect.addEventListener('change', calculate);
    </script>
</body>

</html>