<section class="mb-4 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-100">
    <p class="text-sm text-slate-700">
        Converse diretamente com o assistente para estruturar o seu CV. <strong>Não precisa</strong> de carregar CV ou vaga (JD) aqui — quando tiver o texto final, copie-o para a página da trilha e guarde.
    </p>
</section>
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
    <openai-chatkit id="openai-chatkit-root" class="block w-full min-h-[28rem]"></openai-chatkit>
</div>
<div class="mt-4 flex flex-wrap items-center gap-3">
    <button type="button" onclick="location.reload()" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
        Recarregar chat
    </button>
    @if(!$session->already_rated)
        <button type="button" onclick="showRatingModal({{ $session->id }}, @js($session->agent->name))"
                class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-amber-600">
            Avaliar conversa
        </button>
    @else
        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">Avaliado</span>
    @endif
</div>
