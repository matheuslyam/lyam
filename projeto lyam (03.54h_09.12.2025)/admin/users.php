<?php
// /admin/users.php
require_once 'auth_check.php';
require_once '../config.php';

// Apenas Admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$msg = '';
$editUser = null;

// --- AÇÕES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. CRIAR / EDITAR
    if (isset($_POST['action']) && ($_POST['action'] === 'create' || $_POST['action'] === 'update')) {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS);
        $role = $_POST['role'];
        $commission = str_replace(',', '.', $_POST['commission_rate'] ?? '0'); // Aceita 10,5
        $active = isset($_POST['active']) ? 1 : 0;

        if ($_POST['action'] === 'create') {
            // Novo Usuário
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, commission_rate, active) VALUES (?, ?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$username, $password, $full_name, $role, $commission, $active]);
                $msg = "Usuário criado com sucesso!";
            } catch (PDOException $e) {
                $msg = "Erro: " . $e->getMessage();
            }
        } else {
            // Atualizar
            $id = $_POST['id'];
            $sql = "UPDATE users SET username=?, full_name=?, role=?, commission_rate=?, active=?";
            $params = [$username, $full_name, $role, $commission, $active];

            if (!empty($_POST['password'])) {
                $sql .= ", password=?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id=?";
            $params[] = $id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $msg = "Usuário atualizado!";
        }
    }

    // 2. EXCLUIR
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        if ($id != $_SESSION['user_id']) { // Não se auto-deletar
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            $msg = "Usuário removido.";
        }
    }
}

// Se for edição, busca dados
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lista Usuários
$users = $pdo->query("SELECT * FROM users ORDER BY active DESC, full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br" class="dark">

<head>
    <meta charset="UTF-8">
    <title>Gerenciar Equipe | eBike Solutions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0a0a0a', 800: '#171717', 700: '#262626' },
                        neon: { green: '#4ade80', purple: '#c084fc' }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-dark-900 text-gray-200 min-h-screen p-6 font-sans selection:bg-neon-green selection:text-black">

    <?php include 'navbar.php'; ?>

    <div class="max-w-6xl mx-auto mt-8">

        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white tracking-tight border-l-4 border-neon-green pl-4">Equipe de
                    Vendas</h1>
                <p class="text-neutral-500 text-sm mt-1 ml-5">Gerencie acessos e comissões.</p>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="bg-neon-green/10 border border-neon-green/20 text-neon-green p-4 rounded-xl mb-8 font-bold">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="bg-dark-800 p-6 rounded-2xl border border-dark-700 h-fit shadow-xl">
                <h2 class="text-white font-bold uppercase text-xs tracking-widest mb-6 border-b border-dark-700 pb-2">
                    <?= $editUser ? 'Editar Usuário' : 'Novo Usuário' ?>
                </h2>

                <form method="POST">
                    <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
                    <?php if ($editUser): ?><input type="hidden" name="id" value="<?= $editUser['id'] ?>"><?php endif; ?>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-neutral-500 uppercase mb-1">Nome
                                Completo</label>
                            <input type="text" name="full_name" required
                                value="<?= htmlspecialchars($editUser['full_name'] ?? '') ?>"
                                class="w-full bg-dark-900 border border-dark-700 rounded-lg p-3 text-white focus:border-neon-green outline-none">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-neutral-500 uppercase mb-1">Username
                                (Login)</label>
                            <input type="text" name="username" required
                                value="<?= htmlspecialchars($editUser['username'] ?? '') ?>"
                                class="w-full bg-dark-900 border border-dark-700 rounded-lg p-3 text-white focus:border-neon-green outline-none">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-neutral-500 uppercase mb-1">Senha
                                <?= $editUser ? '(Deixe vazio para manter)' : '*' ?></label>
                            <input type="password" name="password" <?= $editUser ? '' : 'required' ?>
                                class="w-full bg-dark-900 border border-dark-700 rounded-lg p-3 text-white focus:border-neon-green outline-none">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-[10px] font-bold text-neutral-500 uppercase mb-1">Função</label>
                                <select name="role"
                                    class="w-full bg-dark-900 border border-dark-700 rounded-lg p-3 text-white focus:border-neon-green outline-none">
                                    <option value="seller" <?= ($editUser['role'] ?? '') === 'seller' ? 'selected' : '' ?>>
                                        Vendedor</option>
                                    <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                                        Admin</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-neutral-500 uppercase mb-1">Comissão
                                    (%)</label>
                                <input type="text" name="commission_rate"
                                    value="<?= htmlspecialchars($editUser['commission_rate'] ?? '0') ?>"
                                    class="w-full bg-dark-900 border border-dark-700 rounded-lg p-3 text-white focus:border-neon-green outline-none">
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" name="active" id="active"
                                class="w-4 h-4 rounded bg-dark-900 border-dark-700 text-neon-green focus:ring-0"
                                <?= ($editUser['active'] ?? 1) ? 'checked' : '' ?>>
                            <label for="active" class="text-sm text-gray-300 select-none cursor-pointer">Usuário
                                Ativo</label>
                        </div>

                        <div class="pt-4 flex gap-2">
                            <button type="submit"
                                class="flex-1 bg-neon-green hover:bg-green-500 text-black font-bold py-3 rounded-lg transition shadow-lg shadow-green-900/20">
                                <?= $editUser ? 'Salvar Alterações' : 'Criar Usuário' ?>
                            </button>
                            <?php if ($editUser): ?>
                                <a href="users.php"
                                    class="px-4 py-3 bg-dark-700 hover:bg-dark-600 text-white rounded-lg transition">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 bg-dark-800 rounded-2xl border border-dark-700 shadow-xl overflow-hidden">
                <table class="w-full text-left">
                    <thead
                        class="bg-dark-900 border-b border-dark-700 text-[10px] uppercase text-neutral-500 tracking-wider">
                        <tr>
                            <th class="p-4">Nome / Login</th>
                            <th class="p-4">Função</th>
                            <th class="p-4">Comissão</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-700 text-sm">
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-dark-700/50 transition">
                                <td class="p-4">
                                    <div class="font-bold text-white"><?= htmlspecialchars($u['full_name'] ?? 'Sem Nome') ?>
                                    </div>
                                    <div class="text-xs text-neutral-500">@<?= htmlspecialchars($u['username'] ?? '') ?>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span
                                        class="px-2 py-1 rounded text-xs font-bold border <?= $u['role'] === 'admin' ? 'border-purple-500/30 text-purple-400 bg-purple-500/10' : 'border-blue-500/30 text-blue-400 bg-blue-500/10' ?>">
                                        <?= ucfirst($u['role']) ?>
                                    </span>
                                </td>
                                <td class="p-4 font-mono text-gray-300">
                                    <?= number_format($u['commission_rate'] ?? 0, 1) ?>%
                                </td>
                                <td class="p-4">
                                    <span
                                        class="w-2 h-2 rounded-full inline-block mr-2 <?= $u['active'] ? 'bg-neon-green' : 'bg-red-500' ?>"></span>
                                    <span
                                        class="text-xs <?= $u['active'] ? 'text-green-400' : 'text-red-400' ?>"><?= $u['active'] ? 'Ativo' : 'Inativo' ?></span>
                                </td>
                                <td class="p-4 text-right flex justify-end gap-2">
                                    <a href="users.php?edit=<?= $u['id'] ?>"
                                        class="p-2 bg-dark-900 hover:bg-dark-600 rounded border border-dark-600 text-gray-300 transition">✏️</a>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" onsubmit="return confirm('Tem certeza?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <button
                                                class="p-2 bg-red-900/20 hover:bg-red-900/40 border border-red-900/50 text-red-400 rounded transition">🗑️</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</body>

</html>