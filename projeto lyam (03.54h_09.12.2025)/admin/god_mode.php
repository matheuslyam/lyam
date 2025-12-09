<?php
// /admin/god_mode.php - FERRAMENTA DE TESTES (V2 - SNIPER ATIVO)
require_once 'auth_check.php';
require_once '../config.php';
require_once 'sniper_service.php'; // Carrega o Cérebro

// Segurança Máxima
if ($_SESSION['role'] !== 'admin') {
    die("⛔ Acesso restrito ao Criador.");
}

$msg = '';

// --- LÓGICA DE DEUS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. CRIAR EQUIPE DE VENDAS COM HISTÓRICO
    if (isset($_POST['action']) && $_POST['action'] === 'seed_team') {
        try {
            $passHash = password_hash('123456', PASSWORD_DEFAULT);

            // A. O Lobo (Top Performer)
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, active) VALUES ('lobo', ?, 'O Lobo (Top)', 'seller', 1)");
            $stmt->execute([$passHash]);
            $loboId = $pdo->lastInsertId();

            // Injeta 10 Vendas (Won) recentes
            for ($i = 0; $i < 10; $i++) {
                $pdo->prepare("INSERT INTO leads (name, phone, funnel_stage, user_id, score_total, created_at, lead_source) VALUES (?, '11999999999', 'Won', ?, 50, DATE_SUB(NOW(), INTERVAL 2 DAY), 'God Mode')")->execute(["Venda Fake Lobo $i", $loboId]);
            }

            // B. O Estagiário (Baixa Performance)
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, active) VALUES ('estagiario', ?, 'Estagiário (Low)', 'seller', 1)");
            $stmt->execute([$passHash]);
            $estagId = $pdo->lastInsertId();

            // Injeta 1 Venda (Venda Sorte)
            $pdo->prepare("INSERT INTO leads (name, phone, funnel_stage, user_id, score_total, created_at, lead_source) VALUES ('Venda Sorte', '11999999999', 'Won', ?, 20, DATE_SUB(NOW(), INTERVAL 3 DAY), 'God Mode')")->execute([$estagId]);

            // C. O Novato (Zero Vendas)
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, active) VALUES ('novato', ?, 'Novato (Zero)', 'seller', 1)");
            $stmt->execute([$passHash]);

            $msg = "✅ Equipe Semeada: Lobo (10 vendas), Estagiário (1 venda), Novato (0).";
        } catch (Exception $e) {
            $msg = "❌ Erro: " . $e->getMessage();
        }
    }

    // 2. SIMULAR ENTRADA DE LEAD (COM SNIPER)
    if (isset($_POST['action']) && str_starts_with($_POST['action'], 'sim_lead')) {
        $type = $_POST['action'] == 'sim_lead_hot' ? 'HOT' : 'COLD';
        $score = $type == 'HOT' ? 40 : 10;
        $name = "Lead Teste $type " . rand(100, 999);

        // Ativa o Sniper para decidir o dono
        $sniper = new SniperService($pdo);
        $targetId = $sniper->getTargetSeller($score);

        // Busca nome do dono para exibir na mensagem
        $ownerName = "Ninguém";
        if ($targetId) {
            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$targetId]);
            $ownerName = $stmt->fetchColumn();
        }

        $sql = "INSERT INTO leads (name, phone, email, score_total, funnel_stage, lead_source, created_at, user_id) 
                VALUES (?, '11999999999', 'teste@godmode.com', ?, 'New Lead', 'God Mode', NOW(), ?)";
        $pdo->prepare($sql)->execute([$name, $score, $targetId]);

        $msg = "⚡ Lead $type ($score pts) injetado! 🎯 Sniper atribuiu para: <b>$ownerName</b>";
    }

    // 3. LIMPAR DADOS DE TESTE (CORRIGIDO)
    if (isset($_POST['action']) && $_POST['action'] === 'nuke_fakes') {
        // Apaga leads com origem 'God Mode' OU nomes específicos de teste
        $pdo->exec("DELETE FROM leads WHERE lead_source = 'God Mode' OR name LIKE 'Venda Fake%' OR name = 'Venda Sorte'");
        // Apaga os usuários de teste
        $pdo->exec("DELETE FROM users WHERE username IN ('lobo', 'estagiario', 'novato')");
        $msg = "💥 Base Limpa. Cenários destruídos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="dark">

<head>
    <meta charset="UTF-8">
    <title>God Mode | eBike Solutions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0a0a0a', 800: '#171717', 700: '#262626' },
                        neon: { green: '#4ade80', purple: '#c084fc', red: '#f87171' }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-dark-900 text-gray-200 min-h-screen p-10 font-sans selection:bg-neon-purple selection:text-black">

    <div class="max-w-4xl mx-auto">

        <div class="flex items-center gap-4 mb-10 border-b border-dark-700 pb-6">
            <div class="bg-neon-purple/20 p-4 rounded-full text-neon-purple">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-4xl font-bold text-white tracking-tight">GOD MODE <span
                        class="text-xs align-top bg-neon-purple text-black px-2 py-0.5 rounded font-bold">DEV</span>
                </h1>
                <p class="text-neutral-500">Simulação de Cenários & Testes de Stress</p>
            </div>
            <a href="leads.php" class="ml-auto text-sm text-neutral-400 hover:text-white underline">Voltar para o Mundo
                Real</a>
        </div>

        <?php if ($msg): ?>
            <div
                class="bg-dark-800 border-l-4 border-neon-purple p-4 mb-8 text-white shadow-lg animate-pulse flex items-center gap-3">
                <svg class="w-6 h-6 text-neon-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span><?= $msg ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            <div
                class="bg-dark-800 p-8 rounded-2xl border border-dark-700 shadow-xl relative overflow-hidden group hover:border-neon-purple/30 transition">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition"><svg
                        class="w-24 h-24 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg></div>

                <h3 class="text-neon-purple font-bold uppercase text-xs tracking-widest mb-4">Cenário: Equipe de Vendas
                </h3>
                <p class="text-sm text-gray-400 mb-6 min-h-[40px]">Gera 3 perfis: <b>Lobo</b> (Top), <b>Estagiário</b>
                    (Low) e <b>Novato</b> (Zero) com histórico de vendas.</p>

                <form method="POST">
                    <input type="hidden" name="action" value="seed_team">
                    <button type="submit"
                        class="w-full bg-dark-700 hover:bg-neon-purple hover:text-black text-white font-bold py-3 rounded-xl transition-all shadow-lg flex items-center justify-center gap-2">
                        <span>👥 Semear Equipe</span>
                    </button>
                </form>
            </div>

            <div
                class="bg-dark-800 p-8 rounded-2xl border border-dark-700 shadow-xl relative overflow-hidden group hover:border-neon-green/30 transition">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition"><svg
                        class="w-24 h-24 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg></div>

                <h3 class="text-neon-green font-bold uppercase text-xs tracking-widest mb-4">Teste de Distribuição
                    (Sniper)</h3>
                <p class="text-sm text-gray-400 mb-6 min-h-[40px]">Injeta leads e usa o algoritmo para decidir o dono
                    com base no Score.</p>

                <div class="flex gap-4">
                    <form method="POST" class="w-1/2">
                        <input type="hidden" name="action" value="sim_lead_hot">
                        <button type="submit"
                            class="w-full bg-dark-700 hover:bg-neon-green hover:text-black text-white font-bold py-3 rounded-xl transition-all shadow-lg border border-dark-600">
                            🔥 Lead HOT
                        </button>
                    </form>
                    <form method="POST" class="w-1/2">
                        <input type="hidden" name="action" value="sim_lead_cold">
                        <button type="submit"
                            class="w-full bg-dark-700 hover:bg-blue-500 hover:text-white text-white font-bold py-3 rounded-xl transition-all shadow-lg border border-dark-600">
                            🧊 Lead COLD
                        </button>
                    </form>
                </div>
            </div>

            <div
                class="md:col-span-2 bg-red-900/10 p-6 rounded-2xl border border-red-900/30 flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h3 class="text-red-400 font-bold uppercase text-xs tracking-widest flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                        Zona de Perigo
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Remove todos os dados de teste (Leads Fake, Equipe Fake).</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="nuke_fakes">
                    <button type="submit"
                        class="bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white border border-red-500/50 font-bold py-2 px-6 rounded-lg transition-all shadow-lg flex items-center gap-2">
                        ☢️ Limpar Tudo
                    </button>
                </form>
            </div>

        </div>
    </div>

</body>

</html>