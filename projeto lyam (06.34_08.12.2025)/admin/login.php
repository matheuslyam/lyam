<?php
// /admin/login.php
session_start();
require_once '../config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role, full_name, active FROM users WHERE username = :username LIMIT 1");
            $stmt->bindValue(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['active'] == 0) {
                    $error = "Acesso revogado.";
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['last_activity'] = time();
                    header("Location: dashboard.php");
                    exit;
                }
            } else {
                $error = "Credenciais inválidas.";
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $error = "Erro interno.";
        }
    } else {
        $error = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - eBike Solutions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 400: '#4ade80', 500: '#22c55e', card: '#171717', dark: '#0a0a0a' }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-brand-dark h-screen flex items-center justify-center text-gray-200">

    <div class="bg-brand-card p-8 rounded-xl shadow-2xl border border-neutral-800 w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white tracking-tight">eBike<span class="text-brand-400">Solutions</span>
            </h1>
            <p class="text-neutral-500 text-sm mt-1 uppercase tracking-widest">System Access</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-900/20 border border-red-500/50 text-red-400 p-4 mb-6 rounded-lg text-sm"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-5">
                <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">Usuário</label>
                <input
                    class="bg-neutral-900 border border-neutral-700 text-white text-sm rounded-lg focus:ring-brand-400 focus:border-brand-400 block w-full p-3"
                    name="username" type="text" placeholder="user.admin" required>
            </div>

            <div class="mb-8">
                <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">Senha</label>
                <input
                    class="bg-neutral-900 border border-neutral-700 text-white text-sm rounded-lg focus:ring-brand-400 focus:border-brand-400 block w-full p-3"
                    name="password" type="password" placeholder="••••••••" required>
            </div>

            <button
                class="w-full text-black bg-brand-400 hover:bg-brand-500 font-bold rounded-lg text-sm px-5 py-3 transition-all transform hover:scale-[1.02]"
                type="submit">
                ACESSAR PAINEL
            </button>
        </form>
    </div>
</body>

</html>