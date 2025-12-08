<?php
// /admin/dashboard.php
require_once 'auth_check.php';
require_once '../config.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// --- LÓGICA DE NEGÓCIO (BI) ---

// 1. Filtro de Permissão (Admin vê tudo, Seller vê o seu)
$where_user = "";
$params = [];
if ($role !== 'admin') {
    $where_user = " AND user_id = :uid";
    $params[':uid'] = $user_id;
}

try {
    // Métrica 1: Leads Hoje
    $sqlToday = "SELECT COUNT(*) FROM leads WHERE DATE(created_at) = CURDATE() $where_user";
    $stmt = $pdo->prepare($sqlToday);
    $stmt->execute($params);
    $leads_today = $stmt->fetchColumn();

    // Métrica 2: Vendas Confirmadas (Status 'won') neste Mês
    $sqlSales = "SELECT SUM(value) FROM leads 
                 WHERE status = 'won' 
                 AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                 AND YEAR(created_at) = YEAR(CURRENT_DATE()) 
                 $where_user";
    $stmt = $pdo->prepare($sqlSales);
    $stmt->execute($params);
    $sales_month = $stmt->fetchColumn() ?: 0.00; // Se null, vira 0.00

    // Métrica 3: Em Negociação (Pipeline Ativo)
    $sqlPipeline = "SELECT COUNT(*) FROM leads WHERE status = 'negotiation' $where_user";
    $stmt = $pdo->prepare($sqlPipeline);
    $stmt->execute($params);
    $pipeline_active = $stmt->fetchColumn();

    // Métrica 4: Taxa de Conversão Geral (Won / Total)
    $sqlTotal = "SELECT COUNT(*) FROM leads WHERE 1=1 $where_user"; // Total histórico
    $stmt = $pdo->prepare($sqlTotal);
    $stmt->execute($params);
    $total_leads = $stmt->fetchColumn();

    $sqlWonTotal = "SELECT COUNT(*) FROM leads WHERE status = 'won' $where_user";
    $stmt = $pdo->prepare($sqlWonTotal);
    $stmt->execute($params);
    $total_won = $stmt->fetchColumn();

    $conversion_rate = ($total_leads > 0) ? ($total_won / $total_leads) * 100 : 0;

} catch (PDOException $e) {
    // Em produção, logar erro e não exibir
    $leads_today = 0;
    $sales_month = 0;
    $conversion_rate = 0;
    error_log("Dashboard Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="dark">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - eBike Solutions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0a0a0a', 800: '#171717', 700: '#262626' },
                        neon: { green: '#4ade80', blue: '#60a5fa', purple: '#c084fc', orange: '#fb923c' }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-dark-900 text-gray-200 min-h-screen">

    <?php include 'navbar.php'; ?>

    <div class="container mx-auto mt-8 p-4">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-white tracking-tight border-l-4 border-neon-green pl-4">Visão Geral</h1>
            <p class="text-neutral-500 text-sm mt-1 ml-5">Performance em Tempo Real</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <div
                class="bg-dark-800 p-6 rounded-xl border border-dark-700 shadow-lg relative overflow-hidden group hover:border-neon-green transition-all duration-300">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <svg class="w-12 h-12 text-neon-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <h3 class="text-neutral-500 text-xs font-bold uppercase tracking-wider">Leads Hoje</h3>
                <p class="text-4xl font-bold text-white mt-2 group-hover:text-neon-green transition-colors">
                    <?= $leads_today ?>
                </p>
            </div>

            <div
                class="bg-dark-800 p-6 rounded-xl border border-dark-700 shadow-lg relative overflow-hidden group hover:border-neon-orange transition-all duration-300">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <svg class="w-12 h-12 text-neon-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-neutral-500 text-xs font-bold uppercase tracking-wider">Em Negociação</h3>
                <p class="text-4xl font-bold text-white mt-2 group-hover:text-neon-orange transition-colors">
                    <?= $pipeline_active ?>
                </p>
            </div>

            <div
                class="bg-dark-800 p-6 rounded-xl border border-dark-700 shadow-lg relative overflow-hidden group hover:border-neon-blue transition-all duration-300">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <svg class="w-12 h-12 text-neon-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-neutral-500 text-xs font-bold uppercase tracking-wider">Vendas (Mês)</h3>
                <p class="text-3xl font-bold text-white mt-3 group-hover:text-neon-blue transition-colors truncate">
                    R$ <?= number_format($sales_month, 2, ',', '.') ?>
                </p>
            </div>

            <div
                class="bg-dark-800 p-6 rounded-xl border border-dark-700 shadow-lg relative overflow-hidden group hover:border-neon-purple transition-all duration-300">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <svg class="w-12 h-12 text-neon-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-neutral-500 text-xs font-bold uppercase tracking-wider">Taxa de Conversão</h3>
                <p class="text-4xl font-bold text-white mt-2 group-hover:text-neon-purple transition-colors">
                    <?= number_format($conversion_rate, 1, ',', '.') ?>%
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <a href="lead_form.php"
                class="bg-dark-800 hover:bg-dark-700 p-6 rounded-xl border border-dark-700 transition flex items-center gap-4 group">
                <div
                    class="bg-neon-green/20 p-3 rounded-full text-neon-green group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-white">Novo Lead</h4>
                    <p class="text-sm text-neutral-400">Cadastrar novo cliente potencial</p>
                </div>
            </a>

            <a href="leads.php"
                class="bg-dark-800 hover:bg-dark-700 p-6 rounded-xl border border-dark-700 transition flex items-center gap-4 group">
                <div class="bg-blue-500/20 p-3 rounded-full text-blue-400 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-white">Gerenciar Pipeline</h4>
                    <p class="text-sm text-neutral-400">Ver todas as oportunidades ativas</p>
                </div>
            </a>
        </div>

    </div>
</body>

</html>