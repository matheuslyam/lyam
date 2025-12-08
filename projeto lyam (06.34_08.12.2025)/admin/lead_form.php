<?php
// /admin/lead_form.php
require_once 'auth_check.php';
require_once '../config.php';

$curr_user_id = $_SESSION['user_id'];
$curr_role = $_SESSION['role'];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$lead = null;
$error = '';

// --- 1. BUSCA DE DADOS (READ) ---
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = :id");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lead) {
        header("Location: leads.php");
        exit;
    }

    // BLINDAGEM: Se não for Admin, só pode ver seu próprio lead
    if ($curr_role !== 'admin' && ($lead['user_id'] ?? null) !== $curr_user_id) {
        header("Location: leads.php?msg=forbidden");
        exit;
    }
}

// --- 2. LISTA DE VENDEDORES (Apenas para Admin popular o select) ---
$sellers = [];
if ($curr_role === 'admin') {
    $stmt = $pdo->query("SELECT id, full_name FROM users WHERE active = 1 ORDER BY full_name ASC");
    $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- 3. PROCESSAMENTO (CREATE/UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitização
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $phone = preg_replace('/\D/', '', $_POST['phone'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $interest = filter_input(INPUT_POST, 'interest', FILTER_SANITIZE_SPECIAL_CHARS);
    $value = str_replace(['.', ','], ['', '.'], $_POST['value'] ?? 0);
    $source = filter_input(INPUT_POST, 'source', FILTER_SANITIZE_SPECIAL_CHARS);
    $status = $_POST['status'] ?? 'new';
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_SPECIAL_CHARS);

    // Definição do Dono do Lead
    $owner_id = $curr_user_id;
    if ($curr_role === 'admin' && !empty($_POST['owner_id'])) {
        $owner_id = $_POST['owner_id'];
    } elseif ($id && $curr_role === 'admin') {
        $owner_id = $_POST['owner_id'] ?? $lead['user_id'];
    }

    if (!$name || !$phone) {
        $error = "Nome e Telefone são obrigatórios.";
    } else {
        try {
            if ($id) {
                // UPDATE
                // ATENÇÃO: USO DE BACKTICKS (`) AQUI PARA BLINDAGEM
                $sql = "UPDATE leads SET `name`=:name, phone=:phone, email=:email, interest=:interest, 
                        value=:value, source=:source, status=:status, notes=:notes, user_id=:uid 
                        WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':id', $id);
            } else {
                // INSERT
                // ATENÇÃO: USO DE BACKTICKS (`) AQUI PARA BLINDAGEM
                $sql = "INSERT INTO leads (`name`, phone, email, interest, value, source, status, notes, user_id) 
                        VALUES (:name, :phone, :email, :interest, :value, :source, :status, :notes, :uid)";
                $stmt = $pdo->prepare($sql);
            }

            // Binds
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':phone', $phone);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':interest', $interest);
            $stmt->bindValue(':value', $value);
            $stmt->bindValue(':source', $source);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':notes', $notes);
            $stmt->bindValue(':uid', $owner_id);

            $stmt->execute();
            header("Location: leads.php?msg=saved");
            exit;

        } catch (PDOException $e) {
            // Se o erro ainda persistir, é um problema de infraestrutura (cache/typo)
            $error = "Erro no banco: SQLSTATE[{$e->getCode()}]: Column not found: Verifique a estrutura da tabela leads.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br" class="dark">

<head>
    <meta charset="UTF-8">
    <title><?= $id ? 'Editar' : 'Novo' ?> Lead - eBike Solutions</title>
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

    <div class="bg-dark-800 p-8 rounded-xl shadow-2xl border border-dark-700 w-full max-w-2xl">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-white tracking-tight">
                <?= $id ? 'Editar Oportunidade' : 'Novo Lead' ?>
            </h1>
            <a href="leads.php" class="text-neutral-500 hover:text-white transition-colors text-sm">Cancelar</a>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-900/20 border border-red-500/50 text-red-400 p-4 mb-6 rounded text-sm"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">Nome do Cliente *</label>
                    <input name="name" type="text" required
                        class="bg-dark-900 border border-dark-700 text-white text-sm rounded-lg focus:ring-neon-green focus:border-neon-green block w-full p-3"
                        value="<?= htmlspecialchars($lead['name'] ?? '') ?>" placeholder="Ex: João da Silva">
                </div>
                <div>
                    <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">Status</label>
                    <select name="status"
                        class="bg-dark-900 border border-dark-700 text-white text-sm rounded-lg focus:ring-neon-green focus:border-neon-green block w-full p-3">
                        <?php
                        $statuses = [
                            'new' => 'Novo',
                            'contacted' => 'Contatado',
                            'negotiation' => 'Negociação',
                            'won' => 'Venda Feita',
                            'lost' => 'Perdido'
                        ];
                        foreach ($statuses as $key => $label): ?>
                            <option value="<?= $key ?>" <?= ($lead['status'] ?? 'new') === $key ? 'selected' : '' ?>>
                                <?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">WhatsApp / Telefone *</label>
                    <input name="phone" type="text" required
                        class="bg-dark-900 border border-dark-700 text-white text-sm rounded-lg focus:ring-neon-green focus:border-neon-green block w-full p-3"
                        value="<?= htmlspecialchars($lead['phone'] ?? '') ?>" placeholder="(00) 00000-0000">
                </div>
                <div>
                    <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">Email (Opcional)</label>
                    <input name="email" type="email"
                        class="bg-dark-900 border border-dark-700 text-white text-sm rounded-lg focus:ring-neon-green focus:border-neon-green block w-full p-3"
                        value="<?= htmlspecialchars($lead['email'] ?? '') ?>" placeholder="cliente@email.com">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">Interesse</label>
                    <input name="interest" type="text"
                        class="bg-dark-900 border border-dark-700 text-white text-sm rounded-lg focus:ring-neon-green focus:border-neon-green block w-full p-3"
                        value="<?= htmlspecialchars($lead['interest'] ?? '') ?>" placeholder="Ex: Bike Aro 29">
                </div>
                <div>
                    <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">Valor Estimado (R$)</label>
                    <input name="value" type="text"
                        class="bg-dark-900 border border-dark-700 text-white text-sm rounded-lg focus:ring-neon-green focus:border-neon-green block w-full p-3"
                        value="<?= number_format($lead['value'] ?? 0, 2, ',', '.') ?>" placeholder="0,00">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">Origem do Lead</label>
                    <select name="source"
                        class="bg-dark-900 border border-dark-700 text-white text-sm rounded-lg focus:ring-neon-green focus:border-neon-green block w-full p-3">
                        <option value="manual" <?= ($lead['source'] ?? '') === 'manual' ? 'selected' : '' ?>>Manual /
                            Balcão</option>
                        <option value="instagram" <?= ($lead['source'] ?? '') === 'instagram' ? 'selected' : '' ?>>
                            Instagram</option>
                        <option value="google" <?= ($lead['source'] ?? '') === 'google' ? 'selected' : '' ?>>Google Ads
                        </option>
                        <option value="indicacao" <?= ($lead['source'] ?? '') === 'indicacao' ? 'selected' : '' ?>>
                            Indicação</option>
                    </select>
                </div>

                <?php if ($curr_role === 'admin'): ?>
                    <div>
                        <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase text-purple-400">Responsável
                            (Admin)</label>
                        <select name="owner_id"
                            class="bg-dark-900 border border-purple-500/30 text-white text-sm rounded-lg focus:ring-purple-500 focus:border-purple-500 block w-full p-3">
                            <?php foreach ($sellers as $seller): ?>
                                <option value="<?= $seller['id'] ?>" <?= ($lead['user_id'] ?? $curr_user_id) == $seller['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($seller['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-neutral-400 text-xs font-bold mb-2 uppercase">Histórico / Observações</label>
                <textarea name="notes" rows="4"
                    class="bg-dark-900 border border-dark-700 text-white text-sm rounded-lg focus:ring-neon-green focus:border-neon-green block w-full p-3"
                    placeholder="Detalhes da conversa..."><?= htmlspecialchars($lead['notes'] ?? '') ?></textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-4">
                <button type="submit"
                    class="w-full md:w-auto bg-neon-green hover:bg-green-500 text-black font-bold py-3 px-8 rounded-lg shadow-lg shadow-green-500/20 transition-all transform hover:scale-[1.02]">
                    Salvar Lead
                </button>
            </div>

        </form>
    </div>

</body>

</html>