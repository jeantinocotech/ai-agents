<section class="mb-4 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100">
    <p class="text-sm text-slate-700">
        Converse diretamente com o assistente para estruturar o seu CV. <strong>Não precisa</strong> de carregar CV ou vaga (JD) aqui — quando tiver o texto final, copie-o para a página da trilha e guarde.
    </p>
</section>
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
    <openai-chatkit id="openai-chatkit-root" class="block w-full min-h-[28rem]"></openai-chatkit>
</div>
<div id="chat-rating-thumb-row" class="mt-4 flex flex-wrap items-center justify-end gap-2">
    @if(!$session->already_rated)
        <button type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                title="Gostei"
                aria-label="Avaliar positivamente"
                onclick="submitQuickThumbRating({{ $session->id }}, {{ $session->agent_id }}, 5)">
            <i class="fas fa-thumbs-up text-sm" aria-hidden="true"></i>
        </button>
        <button type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                title="Não gostei"
                aria-label="Avaliar negativamente"
                onclick="submitQuickThumbRating({{ $session->id }}, {{ $session->agent_id }}, 1)">
            <i class="fas fa-thumbs-down text-sm" aria-hidden="true"></i>
        </button>
    @else
        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800 ring-1 ring-emerald-200">Avaliado</span>
    @endif
</div>
