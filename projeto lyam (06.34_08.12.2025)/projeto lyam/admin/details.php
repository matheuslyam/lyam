<?php
// ---------------------------------------------------------
// CRM BIKE LEADS - DETALHES DO LEAD (ARQUIVO MESTRE)
// ---------------------------------------------------------

session_start();
require_once '../config.php';

// 1. VERIFICAÇÃO DE SEGURANÇA
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. INICIALIZAÇÃO DE VARIÁVEIS
$lead = null;
$lead_id = $_GET['id'] ?? null;
$message = '';
$error_msg = '';

// 3. DEFINIÇÕES E HELPERS
// Mapeamento de Estágios (Banco de Dados -> Interface)
$funnel_stages = [
    'New Lead' => 'NOVO LEAD',
    'Contact Attempt' => 'TENTATIVA DE CONTATO',
    'Qualified' => 'QUALIFICADO',
    'Negotiation' => 'NEGOCIAÇÃO', // Tradução corrigida
    'Proposal Sent' => 'PROPOSTA ENVIADA',
    'Won/Lost' => 'GANHO/PERDIDO'
];

function format_phone_details($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone ?? '');
    $length = strlen($phone);
    if ($length === 11) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7, 4);
    } elseif ($length === 10) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6, 4);
    }
    return $phone ?: 'Não informado';
}

function display_tags_details($tags)
{
    if (empty($tags) || $tags === 'Análise Pendente (Erro na IA)') {
        return '<span class="text-yellow-500 font-bold text-sm">Análise Pendente</span>';
    }
    $tags_array = explode(',', $tags);
    $html = '<div class="flex flex-wrap gap-2 mt-2">';
    foreach ($tags_array as $tag) {
        $tag = trim($tag);
        if (!empty($tag)) {
            $html .= '<span class="px-3 py-1 text-xs font-bold rounded-full bg-lime-400/10 text-lime-400 border border-lime-400/20">' . $tag . '</span>';
        }
    }
    $html .= '</div>';
    return $html;
}

// 4. LÓGICA DE PROCESSAMENTO (POST e GET)
if ($lead_id) {
    try {
        // A. Processamento de Formulário (Se houver POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $update_fields = [];
            $params = [];

            // Atualizar Notas
            if (isset($_POST['sales_notes'])) {
                $update_fields[] = "sales_notes = ?";
                $params[] = $_POST['sales_notes'];
            }
            // Registrar Contato
            if (isset($_POST['register_contact'])) {
                $update_fields[] = "last_contact_date = NOW()";
            }

            if (!empty($update_fields)) {
                $sql = "UPDATE leads SET " . implode(', ', $update_fields) . " WHERE id = ?";
                $params[] = $lead_id;

                $stmt_update = $pdo->prepare($sql);
                $stmt_update->execute($params);

                // Redireciona para evitar reenvio de formulário e atualizar a tela
                header("Location: details.php?id=$lead_id&msg=updated");
                exit;
            }
        }

        // B. Busca de Dados do Lead
        $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt->execute([$lead_id]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lead) {
            $error_msg = "Lead ID #$lead_id não encontrado no banco de dados.";
        }

    } catch (PDOException $e) {
        die("Erro de Banco de Dados: " . $e->getMessage());
    }
} else {
    $error_msg = "Nenhum ID de lead fornecido.";
}

if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $message = "Lead atualizado com sucesso!";
}

// Se houver erro crítico (Lead não achado), exibe tela de erro amigável e encerra
if ($error_msg) {
    echo '<body style="background:#0b1726; color:#fff; font-family:sans-serif; display:flex; align-items:center; justify-content:center; height:100vh;">';
    echo '<div style="text-align:center;">';
    echo '<h1 style="color:#ef4444; font-size:24px;">Erro</h1>';
    echo '<p>' . htmlspecialchars($error_msg) . '</p>';
    echo '<a href="index.php" style="color:#a3e635; text-decoration:none; margin-top:20px; display:inline-block;">&larr; Voltar ao Kanban</a>';
    echo '</div></body>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes | <?= htmlspecialchars($lead['nome']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-body: #0b1726;
            --bg-card: #1E293B;
            --accent: #a3e635;
            /* Lime-400 */
        }

        body {
            background-color: var(--bg-body);
            color: #e2e8f0;
            font-family: 'Inter', sans-serif;
        }

        .neon-btn {
            background-color: var(--accent);
            color: #0f172a;
            font-weight: bold;
            transition: all 0.2s;
        }

        .neon-btn:hover {
            background-color: #bef264;
            transform: translateY(-1px);
        }

        .input-dark {
            background-color: #0f172a;
            border: 1px solid #334155;
            color: white;
        }

        .input-dark:focus {
            border-color: var(--accent);
            outline: none;
        }
    </style>
</head>

<body class="min-h-screen p-6 lg:p-10">

    <!-- HEADER DE NAVEGAÇÃO -->
    <nav class="flex justify-between items-center mb-8">
        <a href="index.php" class="flex items-center text-gray-400 hover:text-lime-400 transition-colors font-semibold">
            <i class="fas fa-arrow-left mr-2"></i> Voltar ao Pipeline
        </a>
        <div class="text-sm text-gray-500">ID: #<?= $lead['id'] ?></div>
    </nav>

    <!-- MENSAGEM DE SUCESSO -->
    <?php if ($message): ?>
        <div
            class="bg-lime-400/20 border border-lime-400 text-lime-400 px-4 py-3 rounded-lg mb-6 flex items-center shadow-lg shadow-lime-400/10">
            <i class="fas fa-check-circle mr-2"></i> <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- CABEÇALHO DO LEAD -->
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-4xl font-bold text-white mb-2 tracking-tight"><?= htmlspecialchars($lead['nome']) ?></h1>
            <div class="flex items-center gap-3">
                <!-- Estágio do Funil -->
                <span class="px-3 py-1 rounded-full text-sm font-bold bg-[#2A3B4D] text-white border border-gray-600">
                    <i class="fas fa-layer-group text-lime-400 mr-2"></i>
                    <?= $funnel_stages[$lead['funnel_stage']] ?? $lead['funnel_stage'] ?>
                </span>
                <!-- Score -->
                <span
                    class="px-3 py-1 rounded-full text-sm font-bold bg-lime-400/10 text-lime-400 border border-lime-400/20">
                    Score: <?= $lead['score_total'] ?>/40
                </span>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $lead['telefone']) ?>" target="_blank"
                class="neon-btn px-6 py-3 rounded-lg flex items-center shadow-lg shadow-lime-400/20">
                <i class="fab fa-whatsapp text-lg mr-2"></i> Chamar no WhatsApp
            </a>
        </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- COLUNA ESQUERDA (2/3): DADOS E RESPOSTAS -->
        <div class="lg:col-span-2 space-y-8">

            <!-- Card de Inteligência -->
            <div class="bg-[#1E293B] rounded-2xl p-6 border border-gray-700/50 shadow-xl">
                <h2 class="text-xl font-bold text-lime-400 mb-4 flex items-center">
                    <i class="fas fa-brain mr-2"></i> Análise de Inteligência
                </h2>

                <div class="mb-5">
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-2">Tags de Perfil</p>
                    <?= display_tags_details($lead['tags_ai'] ?? '') ?>
                </div>

                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-2">Resumo Estratégico</p>
                    <div class="bg-[#0f172a] p-4 rounded-xl border border-gray-700/50 text-gray-300 leading-relaxed">
                        <?= nl2br(htmlspecialchars($lead['resumo'] ?? 'Aguardando processamento da IA...')) ?>
                    </div>
                </div>
            </div>

            <!-- Card de Respostas do Quiz -->
            <div class="bg-[#1E293B] rounded-2xl p-6 border border-gray-700/50 shadow-xl">
                <h2 class="text-xl font-bold text-lime-400 mb-6 flex items-center">
                    <i class="fas fa-list-ul mr-2"></i> Respostas do Quiz
                </h2>

                <div class="space-y-6">
                    <div class="relative pl-6 border-l-2 border-gray-700">
                        <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-gray-700"></div>
                        <p class="text-sm text-gray-400 mb-1">Missão Principal</p>
                        <p class="text-lg text-white font-medium"><?= htmlspecialchars($lead['q1_missao']) ?></p>
                    </div>
                    <div class="relative pl-6 border-l-2 border-gray-700">
                        <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-gray-700"></div>
                        <p class="text-sm text-gray-400 mb-1">Tipo de Trajeto</p>
                        <p class="text-lg text-white font-medium"><?= htmlspecialchars($lead['q2_trajeto']) ?></p>
                    </div>
                    <div class="relative pl-6 border-l-2 border-gray-700">
                        <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-gray-700"></div>
                        <p class="text-sm text-gray-400 mb-1">Prazo de Compra</p>
                        <p class="text-lg text-white font-medium"><?= htmlspecialchars($lead['q3_prazo']) ?></p>
                    </div>
                </div>
            </div>

        </div>

        <!-- COLUNA DIREITA (1/3): AÇÕES E CONTATO -->
        <div class="space-y-8">

            <!-- Card de Contato -->
            <div class="bg-[#1E293B] rounded-2xl p-6 border border-gray-700/50 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-4 border-b border-gray-700 pb-2">Dados de Contato</h2>
                <ul class="space-y-4">
                    <li class="flex items-center">
                        <div
                            class="w-10 h-10 rounded-lg bg-gray-800 flex items-center justify-center text-lime-400 mr-3">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-xs text-gray-500">Email</p>
                            <p class="text-sm text-white font-mono truncate"
                                title="<?= htmlspecialchars($lead['email']) ?>">
                                <?= htmlspecialchars($lead['email']) ?>
                            </p>
                        </div>
                    </li>
                    <li class="flex items-center">
                        <div
                            class="w-10 h-10 rounded-lg bg-gray-800 flex items-center justify-center text-lime-400 mr-3">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Telefone</p>
                            <p class="text-sm text-white font-mono"><?= format_phone_details($lead['telefone']) ?></p>
                        </div>
                    </li>
                    <li class="flex items-center">
                        <div
                            class="w-10 h-10 rounded-lg bg-gray-800 flex items-center justify-center text-lime-400 mr-3">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Origem</p>
                            <p class="text-sm text-white"><?= htmlspecialchars($lead['lead_source'] ?? 'Quiz') ?></p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Card de Acompanhamento (Formulário) -->
            <div class="bg-[#1E293B] rounded-2xl p-6 border border-gray-700/50 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-4 border-b border-gray-700 pb-2">Acompanhamento</h2>

                <form method="POST" class="space-y-4">

                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Notas do Vendedor</label>
                        <textarea name="sales_notes" rows="6"
                            class="input-dark w-full rounded-xl p-3 text-sm placeholder-gray-600 resize-none"
                            placeholder="Escreva o histórico da negociação..."><?= htmlspecialchars($lead['sales_notes'] ?? '') ?></textarea>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button type="submit" class="neon-btn w-full py-3 rounded-xl flex justify-center items-center">
                            <i class="fas fa-save mr-2"></i> Salvar Notas
                        </button>

                        <button type="submit" name="register_contact"
                            class="bg-blue-600 hover:bg-blue-500 text-white w-full py-3 rounded-xl font-bold transition-colors flex justify-center items-center">
                            <i class="fas fa-history mr-2"></i> Registrar Contato
                        </button>
                    </div>
                </form>

                <div class="mt-4 pt-4 border-t border-gray-700 text-center">
                    <p class="text-xs text-gray-500">Último contato registrado:</p>
                    <p class="text-sm text-white font-mono">
                        <?= $lead['last_contact_date'] ? date('d/m/Y \à\s H:i', strtotime($lead['last_contact_date'])) : 'Nunca' ?>
                    </p>
                </div>
            </div>

        </div>
    </div>

</body>

</html>