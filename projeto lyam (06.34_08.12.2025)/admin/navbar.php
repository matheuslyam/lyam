<?php
// /admin/navbar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_name = $_SESSION['full_name'] ?? 'Usuário';
$user_role = $_SESSION['role'] ?? 'vendedor';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-[#0f0f0f] border-b border-neutral-800 text-white sticky top-0 z-50 backdrop-blur-md bg-opacity-90">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">

        <div class="flex items-center gap-8">
            <a href="dashboard.php"
                class="font-bold text-xl tracking-tight text-white hover:text-green-400 transition-colors duration-300 flex items-center gap-2">
                eBike<span class="text-green-400">Solutions</span>
                <span
                    class="text-[10px] uppercase text-neutral-500 tracking-widest border border-neutral-700 px-1 rounded">Pro</span>
            </a>

            <div class="hidden md:flex gap-1">
                <a href="dashboard.php"
                    class="px-3 py-2 text-sm font-medium rounded-md transition-colors <?= $current_page == 'dashboard.php' ? 'text-white bg-neutral-800' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' ?>">
                    Dashboard
                </a>

                <a href="leads.php"
                    class="px-3 py-2 text-sm font-medium rounded-md transition-colors <?= $current_page == 'leads.php' ? 'text-white bg-neutral-800' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' ?>">
                    Oportunidades
                </a>

                <?php if ($user_role === 'admin'): ?>
                    <a href="users.php"
                        class="px-3 py-2 text-sm font-medium rounded-md transition-colors <?= $current_page == 'users.php' ? 'text-white bg-neutral-800' : 'text-neutral-400 hover:text-white hover:bg-neutral-800/50' ?>">
                        Equipe
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <a href="lead_form.php"
                class="hidden sm:flex items-center gap-2 bg-green-500/10 hover:bg-green-500/20 text-green-400 border border-green-500/50 text-xs font-bold py-2 px-4 rounded-full transition-all duration-300 shadow-[0_0_10px_rgba(74,222,128,0.1)] hover:shadow-[0_0_15px_rgba(74,222,128,0.3)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                NOVO LEAD
            </a>

            <div class="h-6 w-px bg-neutral-800 mx-2 hidden sm:block"></div>

            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-white leading-tight"><?= htmlspecialchars($user_name); ?></p>
                    <p class="text-[10px] text-neutral-500 uppercase tracking-widest font-bold">
                        <?= htmlspecialchars($user_role); ?></p>
                </div>
                <a href="logout.php" class="text-neutral-500 hover:text-red-400 transition-colors" title="Sair">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</nav>