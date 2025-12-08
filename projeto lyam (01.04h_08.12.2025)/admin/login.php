<?php
session_start();
require_once '../config.php';

// Se já estiver logado, joga para o Kanban
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            // Busca o usuário pelo nome
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica se usuário existe E se a senha bate com o hash
            if ($user && password_verify($password, $user['password'])) {
                // Login Sucesso: Cria Sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                header('Location: index.php'); // Redireciona para o Kanban
                exit;
            } else {
                $error = "Usuário ou senha incorretos.";
            }
        } catch (PDOException $e) {
            $error = "Erro no sistema de login.";
            // error_log($e->getMessage()); // Log silencioso em produção
        }
    } else {
        $error = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Bike Leads CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #0b1726;
            color: #fff;
        }

        .neon-btn {
            background-color: #a3e635;
            color: #0b1726;
            font-weight: bold;
            transition: all 0.2s;
        }

        .neon-btn:hover {
            background-color: #bef264;
            transform: scale(1.02);
        }
    </style>
</head>

<body class="h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-[#1E293B] p-8 rounded-2xl shadow-2xl border border-gray-700">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-2">Acesso Restrito</h1>
            <p class="text-gray-400 text-sm">Bike Leads CRM</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-200 p-3 rounded-lg mb-6 text-sm text-center">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-bold mb-2">Usuário</label>
                <input type="text" name="username"
                    class="w-full p-3 rounded-lg bg-[#0f172a] border border-gray-600 focus:border-lime-400 focus:outline-none text-white transition"
                    placeholder="admin" required>
            </div>
            <div class="mb-8">
                <label class="block text-gray-300 text-sm font-bold mb-2">Senha</label>
                <input type="password" name="password"
                    class="w-full p-3 rounded-lg bg-[#0f172a] border border-gray-600 focus:border-lime-400 focus:outline-none text-white transition"
                    placeholder="••••••" required>
            </div>
            <button type="submit"
                class="w-full neon-btn py-3 rounded-xl uppercase tracking-wider text-sm shadow-lg shadow-lime-400/20">
                Entrar no Painel
            </button>
        </form>
    </div>
</body>

</html>