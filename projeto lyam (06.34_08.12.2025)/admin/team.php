<?php
session_start();
require_once '../config.php';

// Apenas Admin pode acessar
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'seller') !== 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';

// Adicionar Novo Usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = trim($_POST['username'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $commission = floatval($_POST['commission'] ?? 0.0);
    $role = $_POST['role'] ?? 'seller';

    if (!empty($username) && !empty($password_raw)) {
        // Verifica se usuário já existe
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->execute([$username]);
        if ($stmt_check->fetch()) {
            $message = "<span class='text-red-400'>Erro: O usuário '$username' já existe.</span>";
        } else {
            $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, commission_rate, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $password_hash, $full_name, $commission, $role]);
                $message = "<span class='text-green-400'>Usuário cadastrado com sucesso!</span>";
            } catch (PDOException $e) {
                $message = "<span class='text-red-400'>Erro ao cadastrar: " . $e->getMessage() . "</span>";
            }
        }
    } else {
        $message = "<span class='text-red-400'>Preencha todos os campos obrigatórios.</span>";
    }
}

// Listar Usuários
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gestão de Equipe | Lyam CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0b1726;
            font-family: 'Inter', sans-serif;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #0b1726;
        }

        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
    </style>
</head>

<body class="text-white min-h-screen p-6 lg:p-10">

    <div class="max-w-7xl mx-auto">
        <!-- Cabeçalho -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white tracking-tight">Gestão de <span
                        class="text-[#a3e635]">Equipe</span></h1>
                <p class="text-gray-400 text-sm mt-1">Gerencie vendedores, permissões e comissões.</p>
            </div>
            <a href="index.php"
                class="bg-[#1E293B] hover:bg-[#334155] text-white px-5 py-2.5 rounded-lg transition flex items-center shadow-lg border border-gray-700">
                <i class="fas fa-arrow-left mr-2 text-[#a3e635]"></i> Voltar ao Kanban
            </a>
        </div>

        <?php if ($message): ?>
            <div class="bg-[#1E293B] border border-gray-600 p-4 rounded-xl mb-8 flex items-center shadow-lg animate-pulse">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

            <!-- COLUNA 1: Formulário de Cadastro -->
            <div class="bg-[#1E293B] p-8 rounded-2xl border border-gray-700 shadow-2xl h-fit">
                <h2 class="text-xl font-bold mb-6 text-white border-b border-gray-700 pb-4 flex items-center">
                    <div
                        class="w-8 h-8 rounded-lg bg-[#a3e635]/20 flex items-center justify-center mr-3 text-[#a3e635]">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    Novo Membro
                </h2>

                <form method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="add">

                    <div>
                        <label class="block text-xs uppercase font-bold text-gray-400 mb-1 tracking-wider">Nome
                            Completo</label>
                        <input type="text" name="full_name" required
                            class="w-full bg-[#0f172a] border border-gray-600 rounded-lg p-3 text-white focus:border-[#a3e635] outline-none transition placeholder-gray-600"
                            placeholder="Ex: João Silva">
                    </div>

                    <div>
                        <label class="block text-xs uppercase font-bold text-gray-400 mb-1 tracking-wider">Usuário
                            (Login)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-gray-500">@</span>
                            <input type="text" name="username" required
                                class="w-full bg-[#0f172a] border border-gray-600 rounded-lg p-3 pl-8 text-white focus:border-[#a3e635] outline-none transition"
                                placeholder="joao.vendas">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs uppercase font-bold text-gray-400 mb-1 tracking-wider">Senha
                            Inicial</label>
                        <input type="password" name="password" required
                            class="w-full bg-[#0f172a] border border-gray-600 rounded-lg p-3 text-white focus:border-[#a3e635] outline-none transition"
                            placeholder="••••••">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs uppercase font-bold text-gray-400 mb-1 tracking-wider">Cargo</label>
                            <select name="role"
                                class="w-full bg-[#0f172a] border border-gray-600 rounded-lg p-3 text-white focus:border-[#a3e635] outline-none cursor-pointer">
                                <option value="seller">Vendedor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs uppercase font-bold text-gray-400 mb-1 tracking-wider">Comissão
                                (%)</label>
                            <input type="number" step="0.1" name="commission" value="0.0"
                                class="w-full bg-[#0f172a] border border-gray-600 rounded-lg p-3 text-white focus:border-[#a3e635] outline-none text-right">
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-[#a3e635] hover:bg-[#bef264] text-[#0b1726] font-bold py-3.5 rounded-xl transition shadow-lg shadow-[#a3e635]/20 mt-2 flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i> Cadastrar Usuário
                    </button>
                </form>
            </div>

            <!-- COLUNA 2: Lista de Membros -->
            <div class="xl:col-span-2 bg-[#1E293B] p-8 rounded-2xl border border-gray-700 shadow-2xl">
                <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center mr-3 text-blue-400">
                            <i class="fas fa-users"></i>
                        </div>
                        Membros Ativos
                    </h2>
                    <span class="bg-gray-700 text-xs px-2 py-1 rounded text-gray-300"><?= count($users) ?>
                        usuários</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-400 text-xs uppercase border-b border-gray-700 tracking-wider">
                                <th class="py-4 pl-4 font-bold">Colaborador</th>
                                <th class="py-4 font-bold">Acesso</th>
                                <th class="py-4 font-bold text-center">Comissão</th>
                                <th class="py-4 font-bold text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-700/50">
                            <?php foreach ($users as $user):
                                $initial = strtoupper(substr($user['username'], 0, 1));
                                $role_color = $user['role'] === 'admin' ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/20';
                                $role_icon = $user['role'] === 'admin' ? 'fa-shield-alt' : 'fa-headset';
                                $display_name = !empty($user['full_name']) ? htmlspecialchars($user['full_name']) : '<span class="italic text-gray-500">Sem nome definido</span>';
                                ?>
                                <tr class="hover:bg-white/5 transition group">
                                    <td class="py-4 pl-4">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-[#a3e635] font-bold text-lg border border-gray-600 shadow-sm group-hover:border-[#a3e635] transition">
                                                <?= $initial ?>
                                            </div>
                                            <div>
                                                <p class="text-white font-medium text-base"><?= $display_name ?></p>
                                                <p class="text-xs text-gray-400 mt-0.5">
                                                    @<?= htmlspecialchars($user['username']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-bold border <?= $role_color ?> inline-flex items-center gap-2">
                                            <i class="fas <?= $role_icon ?>"></i>
                                            <?= strtoupper($user['role']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 text-center font-mono text-gray-300">
                                        <?= number_format($user['commission_rate'], 1, ',', '.') ?>%
                                    </td>
                                    <td class="py-4 text-center">
                                        <?php if ($user['active']): ?>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <span class="w-1.5 h-1.5 mr-1.5 bg-green-600 rounded-full"></span> Ativo
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <span class="w-1.5 h-1.5 mr-1.5 bg-red-600 rounded-full"></span> Inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>