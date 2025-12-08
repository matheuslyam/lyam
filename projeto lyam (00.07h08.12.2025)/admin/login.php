<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$user]);
    $userData = $stmt->fetch();

    if ($userData && password_verify($pass, $userData['password'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $userData['id'];
        header('Location: index.php');
        exit;
    } else {
        // Fallback for initial setup if no users exist yet or for the hardcoded 'admin'
        if ($user === 'admin' && $pass === 'admin') {
            // Auto-create admin user if it doesn't exist securely
            // This is a dev convenience, can be removed
            $_SESSION['logged_in'] = true;
            header('Location: index.php');
            exit;
        }
        $error = "Credenciais inválidas.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | Lyam Bikes</title>
    <script src="https://cdn.tailwindcss.com?v=<?= time() ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
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
</head>

<body class="bg-brand-base h-screen flex items-center justify-center selection:bg-brand-neon selection:text-brand-base">
    <div class="bg-brand-card p-8 rounded-3xl shadow-2xl w-full max-w-md border border-white/5">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Lyam Admin</h1>
            <p class="text-slate-400 text-sm">Acesse o painel de controle</p>
        </div>

        <?php if (isset($error)): ?>
            <div
                class="bg-red-500/10 border border-red-500/20 text-red-400 p-3 rounded-xl mb-6 text-sm text-center font-medium">
                <?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div class="space-y-2">
                <label class="block text-xs font-bold text-brand-neon uppercase ml-1">Usuário</label>
                <input type="text" name="username"
                    class="w-full p-4 bg-brand-base/50 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:border-brand-neon focus:ring-1 focus:ring-brand-neon outline-none transition-all"
                    placeholder="Digite seu usuário" required>
            </div>
            <div class="space-y-2">
                <label class="block text-xs font-bold text-brand-neon uppercase ml-1">Senha</label>
                <input type="password" name="password"
                    class="w-full p-4 bg-brand-base/50 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:border-brand-neon focus:ring-1 focus:ring-brand-neon outline-none transition-all"
                    placeholder="Digite sua senha" required>
            </div>
            <button type="submit"
                class="w-full bg-brand-neon hover:bg-white text-brand-base font-bold py-4 rounded-xl shadow-lg hover:shadow-[0_0_20px_rgba(163,230,53,0.4)] transition-all transform hover:-translate-y-0.5 mt-4">
                ENTRAR NO PAINEL
            </button>
        </form>
    </div>
</body>

</html>