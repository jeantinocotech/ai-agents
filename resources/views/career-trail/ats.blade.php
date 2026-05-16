@php
    $atsChatHeading = config('career_trail.ats_chat_heading', 'Passar no filtro');
    $atsGracaFallback = 'Associe um CV do perfil a cada vaga (JD) abaixo. Com um par completo, poderá abrir «'.$atsChatHeading.'» para alinhar o texto à vaga.';
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Passo 2 — {{ $atsStep->title }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">

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

            <x-graca-orientation-panel :page-key="\App\Support\GracaPanelPreferences::PAGE_TRAIL_ATS">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div class="shrink-0 rounded-2xl bg-white p-1 shadow ring-1 ring-violet-100">
                        <img src="{{ asset(config('career_trail.mentor_avatar', 'img/graca-avatar.png')) }}"
                             alt="{{ config('career_trail.mentor_label', 'Sra. Graça') }}"
                             class="h-16 w-16 rounded-xl object-cover" />
                    </div>
                    <div class="min-w-0 flex-1 text-sm leading-relaxed text-slate-700">
                        <x-graca-slot
                            :placement="\App\Support\CareerTrailGracaSlots::TRAIL_STEP_HEADER"
                            :step="$atsStep"
                            paragraph-class="text-sm leading-relaxed text-slate-700"
                            :fallback="$atsGracaFallback"
                        />
                    </div>
                </div>
            </x-graca-orientation-panel>
            @if ($atsBackendMisconfigured ?? false)
                <div class="mb-4 rounded-xl border border-amber-200/90 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 shadow-sm ring-1 ring-inset ring-amber-100/80" role="alert">
                    <p class="font-semibold text-amber-950">ATS check indisponível</p>
                    <p class="mt-1 text-sm leading-relaxed text-amber-950/95">
                        O agente precisa de ChatKit (workflow), Assistant ID ou passos CV/JD. Corrija na administração.
                    </p>
                </div>
            @endif

            @if (! $atsAgentActive || ! $atsAgent)
                <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50/90 px-4 py-4 text-sm text-slate-700">
                    <p class="font-semibold text-slate-900">Assistente ATS ainda não está configurado</p>
                    <p class="mt-1 text-sm text-slate-700">
                        Para guardar vagas e usar o ATS check, associe um agente à etapa ATS (ou <code class="rounded bg-white px-1 py-0.5 font-mono text-[11px]">CAREER_TRAIL_ATS_AGENT_ID</code>).
                    </p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('career-trail.index') }}"
                           class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                            Ver mapa da trilha
                        </a>
                    </div>
                </div>
            @endif

            @if ($atsAgentActive && $atsAgent && ! empty($libraryPayload))
                <div id="ats-biblioteca" class="scroll-mt-28 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3 sm:px-5">
                        <h1 class="text-xl font-bold text-slate-900">Vagas</h1>
                    </div>
                    @include('agents.documents.partials.library-forms', array_merge(
                        [
                            'agent' => $atsAgent,
                            'trailReturnCareerTrailAts' => true,
                            'suppressSessionFlash' => true,
                            'omitHeading' => true,
                            'editingJd' => $editingJd ?? null,
                            'atsAnalyzeChatUrl' => $atsAnalyzeChatUrl ?? null,
                            'atsChatHeading' => $atsChatHeading,
                            'interviewPrepAgent' => $interviewPrepAgent ?? null,
                            'canAccessInterviewPrep' => $canAccessInterviewPrep ?? false,
                        ],
                        $libraryPayload
                    ))
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
