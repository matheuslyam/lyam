<?php
// /admin/user_form.php
require_once 'auth_check.php';
require_once '../config.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user = null;
$error = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        header("Location: users.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mesma lógica de processamento do anterior, omitida para brevidade
    // (A lógica PHP é idêntica à versão segura que te passei antes)
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $role = $_POST['role'] ?? 'seller';
    $commission = filter_input(INPUT_POST, 'commission_rate', FILTER_VALIDATE_FLOAT);
    $active = isset($_POST['active']) ? 1 : 0;
    $password = $_POST['password'];

    if (!$full_name || !$username || ($commission === false)) {
        $error = "Dados inválidos.";
    } else {
        try {
            if ($id) {
                $sql = "UPDATE users SET full_name=:fn, username=:user, role=:role, commission_rate=:comm, active=:active";
                if (!empty($password))
                    $sql .= ", password=:pass";
                $sql .= " WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                if (!empty($password))
                    $stmt->bindValue(':pass', password_hash($password, PASSWORD_DEFAULT));
                $stmt->bindValue(':id', $id);
            } else {
                if (empty($password))
                    throw new Exception("Senha obrigatória.");
                $stmt = $pdo->prepare("INSERT INTO users (full_name, username, role, commission_rate, active, password) VALUES (:fn, :user, :role, :comm, :active, :pass)");
                $stmt->bindValue(':pass', password_hash($password, PASSWORD_DEFAULT));
            }
            $stmt->bindValue(':fn', $full_name);
            $stmt->bindValue(':user', $username);
            $stmt->bindValue(':role', $role);
            $stmt->bindValue(':comm', $commission);
            $stmt->bindValue(':active', $active);
            $stmt->execute();
            header("Location: users.php?msg=saved");
            exit;
        } catch (Exception $e) {
            $error = "Erro: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="dark">

<head>
    <meta charset="UTF-8">
    <title><?= $id ? 'Editar' : 'Novo' ?> Usuário</title>
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

<body class="bg-dark-900 min-h-screen flex items-center justify-center p-4">

    <div class="bg-dark-800 p-8 rounded-xl shadow-2xl border border-dark-700 w-full max-w-lg">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-white"><?= $id ? 'Editar' : 'Criar' ?> Usuário</h1>
            <a href="users.php" class="text-neutral-500 hover:text-white transition-colors text-sm">Cancelar</a>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-900/20 border border-red-500/50 text-red-400 p-4 mb-4 rounded text-sm"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-5">
                <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">Nome Completo</label>
                <input name="full_name" type="text" required
                    class="bg-dark-900 border border-dark-700 text-white text-sm rounded-lg focus:ring-neon-green focus:border-neon-green block w-full p-3 transition-colors"
                    value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
            </div>

            <div class="mb-5">
                <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">Login</label>
                <input name="username" type="text" required
                    class="bg-dark-900 border border-dark-700 text-white text-sm rounded-lg focus:ring-neon-green focus:border-neon-green block w-full p-3 transition-colors"
                    value="<?= htmlspecialchars($user['username'] ?? '') ?>">
            </div>

            <div class="mb-5">
                <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">
                    Senha
                    <?= $id ? '<span class="text-[10px] font-normal text-neutral-600 ml-1">(Vazio mantém atual)</span>' : '<span class="text-red-500">*</span>' ?>
                </label>
                <input name="password" type="password" <?= $id ? '' : 'required' ?>
                    class="bg-dark-900 border border-dark-700 text-white text-sm rounded-lg focus:ring-neon-green focus:border-neon-green block w-full p-3 transition-colors">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">Perfil</label>
                    <select name="role"
                        class="bg-dark-900 border border-dark-700 text-white text-sm rounded-lg focus:ring-neon-green focus:border-neon-green block w-full p-3">
                        <option value="seller" <?= ($user['role'] ?? '') === 'seller' ? 'selected' : '' ?>>Vendedor
                        </option>
                        <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">Comissão (%)</label>
                    <input name="commission_rate" type="number" step="0.01" min="0"
                        value="<?= $user['commission_rate'] ?? '0.00' ?>"
                        class="bg-dark-900 border border-dark-700 text-white text-sm rounded-lg focus:ring-neon-green focus:border-neon-green block w-full p-3">
                </div>
            </div>

            <div class="mb-8 flex items-center bg-dark-900 p-3 rounded border border-dark-700">
                <input type="checkbox" name="active" id="active"
                    class="w-4 h-4 text-neon-green bg-dark-800 border-dark-700 rounded focus:ring-neon-green"
                    <?= (!isset($user) || $user['active']) ? 'checked' : '' ?>>
                <label for="active" class="ml-3 text-sm font-bold text-gray-300">Usuário Ativo</label>
            </div>

            <button type="submit"
                class="w-full bg-neon-green hover:bg-green-500 text-black font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 transform hover:translate-y-[-1px]">
                <?= $id ? 'Salvar Alterações' : 'Criar Usuário' ?>
            </button>
        </form>
    </div>
</body>

</html>