<div class="bg-brand-base/40 p-5 rounded-2xl shadow-sm border border-white/5 cursor-move hover:border-brand-neon/50 hover:shadow-[0_0_15px_rgba(163,230,53,0.1)] transition-all group"
    data-id="<?= $lead['id'] ?>">
    <div class="flex justify-between items-start mb-3">
        <div class="w-2/3">
            <h3 class="font-bold text-white text-lg group-hover:text-brand-neon transition-colors truncate">
                <?= htmlspecialchars($lead['nome']) ?>
            </h3>
            <div class="text-xs text-slate-400 mt-1 flex flex-col gap-0.5">
                <span><?= formatPhoneNumber($lead['telefone']) ?></span>
                <span class="text-slate-500 truncate"><?= htmlspecialchars($lead['email']) ?></span>
            </div>
        </div>
        <div class="flex flex-col items-end gap-1">
            <?php if (!empty($lead['last_contact_date'])): ?>
                <div class="flex items-center gap-1 text-xs text-slate-400" title="Último Contato">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <?= date('d/m', strtotime($lead['last_contact_date'])) ?>
                </div>
            <?php endif; ?>

            <!-- Score Badge -->
            <div class="bg-brand-card border border-white/10 px-2 py-0.5 rounded text-[10px] font-bold text-slate-300">
                Score: <span
                    class="<?= $lead['score_total'] >= 30 ? 'text-brand-neon' : ($lead['score_total'] >= 15 ? 'text-yellow-400' : 'text-slate-400') ?>"><?= $lead['score_total'] ?></span>/40
            </div>
        </div>
    </div>

    <!-- Tags AI -->
    <div class="mb-4">
        <p class="text-[10px] uppercase font-bold text-slate-500 mb-1.5 tracking-wider">Análise de Inteligência</p>
        <div class="flex flex-wrap gap-2">
            <?php
            $tags = json_decode($lead['tags_ai'] ?? '[]', true);
            if (is_array($tags) && !empty($tags)):
                foreach (array_slice($tags, 0, 3) as $tag):
                    ?>
                    <span
                        class="text-[10px] uppercase font-bold px-2 py-1 rounded bg-brand-neon/10 text-brand-neon border border-brand-neon/20"><?= htmlspecialchars($tag) ?></span>
                    <?php
                endforeach;
            else:
                ?>
                <span class="text-[10px] text-slate-600 italic">Aguardando análise...</span>
                <?php
            endif;
            ?>
        </div>
    </div>

    <div class="flex justify-between items-center mt-2 pt-3 border-t border-white/5">
        <span class="text-xs text-slate-500 font-medium"><?= date('d/m H:i', strtotime($lead['created_at'])) ?></span>
        <a href="details.php?id=<?= $lead['id'] ?>"
            class="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1 transition-colors">
            Detalhes
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
    </div>
</div>