<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Passo 2 — Conseguir uma entrevista, passar pelo filtro (ATS)
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('career-trail.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                    &larr; Voltar à trilha
                </a>
                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-900">Etapa atual na trilha · ATS</span>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-6 overflow-hidden rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 via-white to-indigo-50/80 shadow-sm">
                <div class="border-b border-violet-100/80 bg-white/60 px-6 py-6 sm:px-8">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                        <div class="shrink-0 rounded-2xl bg-gradient-to-br from-violet-500/20 to-indigo-600/20 p-1 shadow-md ring-4 ring-white">
                            <x-graca-avatar size="lg" class="ring-0 shadow-sm" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">A sua guia neste passo</p>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ config('career_trail.mentor_label', 'Sra. Graça') }}</h1>
                            <x-graca-slot
                                :placement="\App\Support\CareerTrailGracaSlots::TRAIL_STEP_HEADER"
                                :step="$atsStep"
                                paragraph-class="mt-2 text-sm leading-relaxed text-slate-600"
                                :fallback="'Associe um CV do perfil a cada vaga (JD) abaixo. Quando houver pelo menos um par completo, você poderá abrir o ATS check para alinhar o texto à vaga.'"
                            />

                            @if ($atsAgentActive && $atsAgent && $atsAllowsCheck)
                                <div class="mt-4 flex flex-wrap items-center gap-3">
                                    <a href="{{ route('agents.chat', $atsAgent) }}"
                                       class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                                        ATS filtro
                                    </a>
                                </div>
                            @endif

                            @if ($atsBackendMisconfigured ?? false)
                                <div class="mt-4 rounded-xl border border-amber-200/90 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 shadow-sm ring-1 ring-inset ring-amber-100/80" role="alert">
                                    <p class="font-semibold text-amber-950">ATS check indisponível</p>
                                    <p class="mt-1 text-sm leading-relaxed text-amber-950/95">
                                        O agente da etapa ATS precisa ter ChatKit (com ID de workflow), OpenAI com Assistant ID ou modo clássico com passos CV/JD.
                                        Na administração, corrija a integração, o Assistant ID ou os passos.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="space-y-5 border-t border-violet-100/80 bg-white/50 px-6 py-6 sm:px-8">
                    @if (count($checklist ?? []) > 0 || (! ($readiness['ready'] ?? false) && ! empty($readiness['blocked_message'])))
                        <div class="rounded-xl border border-amber-200/90 bg-amber-50/80 px-4 py-4">
                            <h2 class="text-sm font-semibold text-amber-950">Para concluir esta etapa e avançar</h2>
                            @if (count($checklist ?? []) > 0)
                                <ul class="mt-3 space-y-2 text-sm">
                                    @foreach ($checklist as $item)
                                        <li @class([
                                            'flex gap-2',
                                            'text-emerald-900' => $item['done'],
                                            'text-slate-700' => ! $item['done'],
                                        ])>
                                            <span class="shrink-0 font-bold" aria-hidden="true">{{ $item['done'] ? '✓' : '○' }}</span>
                                            <span>{{ $item['label'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @if (! ($readiness['ready'] ?? false) && ! empty($readiness['blocked_message']))
                                <p class="mt-3 text-xs text-amber-950/90">{{ $readiness['blocked_message'] }}</p>
                            @endif
                        </div>
                    @endif

                    @if (! $atsAgentActive || ! $atsAgent)
                        <div class="rounded-xl border border-slate-200 bg-slate-50/90 px-4 py-4 text-sm text-slate-700">
                            <p class="font-semibold text-slate-900">Assistente ATS ainda não está configurado</p>
                            <p class="mt-1 text-sm text-slate-700">
                                Para salvar vagas e usar o ATS check, o administrador precisa associar um agente à etapa ATS
                                (ou definir <code class="rounded bg-white px-1 py-0.5 font-mono text-[11px]">CAREER_TRAIL_ATS_AGENT_ID</code>).
                            </p>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <a href="{{ route('career-trail.index') }}"
                                   class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                    Ver mapa da trilha
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($atsAgentActive && $atsAgent && ! empty($libraryPayload))
                <div id="ats-biblioteca" class="scroll-mt-28 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm ring-1 ring-slate-100">
                    <div class="border-b border-slate-100 bg-slate-50/90 px-6 py-4 sm:px-8">
                        <h2 class="text-lg font-semibold text-slate-900">Biblioteca ATS — CV do perfil e vagas (JD)</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Crie ou edite vagas e escolha o CV de perfil para cada uma. Depois de salvar, o botão <strong class="text-slate-800">ATS check</strong> abre o assistente com um par válido.
                        </p>
                    </div>
                    @include('agents.documents.partials.library-forms', array_merge(
                        [
                            'agent' => $atsAgent,
                            'trailReturnCareerTrailAts' => true,
                            'suppressSessionFlash' => true,
                            'omitHeading' => true,
                        ],
                        $libraryPayload
                    ))
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
