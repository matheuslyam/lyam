<?php
// /admin/users.php
require_once 'auth_check.php';
require_once '../config.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php?msg=forbidden");
    exit;
}

try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="dark">

<head>
    <meta charset="UTF-8">
    <title>Equipe - eBike Solutions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0a0a0a', 800: '#171717', 700: '#262626' },
                        neon: { green: '#4ade80' }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-dark-900 text-gray-200 min-h-screen">

    <?php include 'navbar.php'; ?>

    <div class="container mx-auto p-4">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-white tracking-tight border-l-4 border-neon-green pl-4">Equipe</h1>
            <a href="user_form.php"
                class="bg-neon-green hover:bg-green-500 text-black font-bold py-2 px-6 rounded-lg transition-all shadow-lg shadow-green-500/20">
                + Novo Usuário
            </a>
        </div>

        <div class="bg-dark-800 shadow-xl rounded-xl border border-dark-700 overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr>
                        <th
                            class="px-5 py-4 border-b border-dark-700 bg-dark-900 text-left text-xs font-bold text-neutral-400 uppercase tracking-wider">
                            Usuário</th>
                        <th
                            class="px-5 py-4 border-b border-dark-700 bg-dark-900 text-left text-xs font-bold text-neutral-400 uppercase tracking-wider">
                            Cargo</th>
                        <th
                            class="px-5 py-4 border-b border-dark-700 bg-dark-900 text-left text-xs font-bold text-neutral-400 uppercase tracking-wider">
                            Comissão</th>
                        <th
                            class="px-5 py-4 border-b border-dark-700 bg-dark-900 text-left text-xs font-bold text-neutral-400 uppercase tracking-wider">
                            Status</th>
                        <th class="px-5 py-4 border-b border-dark-700 bg-dark-900 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dark-700">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-dark-700/50 transition-colors">
                            <td class="px-5 py-4 bg-transparent text-sm">
                                <p class="text-white font-bold"><?= htmlspecialchars($u['full_name']) ?></p>
                                <p class="text-neutral-500 text-xs">@<?= htmlspecialchars($u['username']) ?></p>
                            </td>
                            <td class="px-5 py-4 bg-transparent text-sm">
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span
                                        class="px-3 py-1 text-xs font-bold text-purple-400 bg-purple-900/30 border border-purple-500/30 rounded-full">
                                        ADMIN
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="px-3 py-1 text-xs font-bold text-blue-400 bg-blue-900/30 border border-blue-500/30 rounded-full">
                                        VENDEDOR
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 bg-transparent text-sm text-neutral-300">
                                <?= number_format($u['commission_rate'], 2, ',', '.') ?>%
                            </td>
                            <td class="px-5 py-4 bg-transparent text-sm">
                                <?php if ($u['active']): ?>
                                    <span class="text-green-400 text-xs font-bold uppercase flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Ativo
                                    </span>
                                <?php else: ?>
                                    <span class="text-red-500 text-xs font-bold uppercase">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 bg-transparent text-sm text-right">
                                <a href="user_form.php?id=<?= $u['id'] ?>"
                                    class="text-neutral-400 hover:text-white font-medium transition-colors">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>