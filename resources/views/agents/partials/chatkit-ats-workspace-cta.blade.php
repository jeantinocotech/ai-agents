<section id="chatkit-ats-workspace-cta"
         class="mb-3 hidden rounded-xl border border-violet-200/90 bg-gradient-to-r from-violet-50/80 to-indigo-50/50 px-3 py-2.5 shadow-sm ring-1 ring-violet-100"
         data-store-url="{{ route('career-trail.ats.analyses.store') }}">
    <div class="mt-2 flex flex-wrap gap-2" id="chatkit-ats-workspace-cta-actions"></div>
    <div id="chatkit-ats-paste-panel" class="mt-3 hidden rounded-lg border border-amber-200/90 bg-amber-50/60 p-3">
        <p class="text-[11px] leading-snug text-amber-950">
            O chat OpenAI não permite ler a tabela automaticamente. Selecione a tabela no chat, copie (<kbd class="rounded border border-amber-300 bg-white px-1">Ctrl+C</kbd>) e cole abaixo:
        </p>
        <textarea id="chatkit-ats-paste-input"
                  rows="5"
                  class="mt-2 w-full rounded-md border-amber-200 bg-white p-2 font-mono text-[11px] text-slate-800 shadow-sm focus:border-violet-500 focus:ring-1 focus:ring-violet-500"
                  placeholder="| Keyword | Prioridade | Status | Score |&#10;| Scrum | Alta | Parcial | 60% |"></textarea>
        <div class="mt-2 flex flex-wrap gap-2">
            <button type="button"
                    id="chatkit-ats-paste-save"
                    class="inline-flex items-center rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-violet-700">
                Guardar tabela colada
            </button>
            <button type="button"
                    id="chatkit-ats-paste-try-clipboard"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                Usar texto da área de transferência
            </button>
        </div>
    </div>
</section>
