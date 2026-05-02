<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            A sua trilha de carreira
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('info'))
                <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800" role="status">
                    {{ session('info') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $maxReached = (int) ($progress->max_sort_order_reached ?? $currentStep->sort_order);
                /** @var \App\Models\User $trailMapUser — alinhamento com badge da trilha suspensa */
                $trailMapUser = auth()->user();
            @endphp

            @if (count($currentStepChecklist ?? []) > 0)
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50/80 px-5 py-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-amber-950">Para concluir esta etapa e avançar</h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($currentStepChecklist as $item)
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
                    @if (! ($currentStepReadiness['ready'] ?? false) && ! empty($currentStepReadiness['blocked_message']))
                        <p class="mt-3 text-xs text-amber-950/90">{{ $currentStepReadiness['blocked_message'] }}</p>
                    @endif
                    @if ($currentStep->slug === 'ats')
                        <p class="mt-3 text-xs text-amber-950/70">
                            Pode candidatar-se a várias vagas em paralelo na biblioteca ATS; para avançar na trilha basta ter <strong>pelo menos uma</strong> vaga com JD e CV associados. Quando cumprir estes requisitos, <strong>Motivação</strong> (opcional) e <strong>Entrevista</strong> ficam disponíveis em paralelo na barra; ao premir «Avançar», a etapa inicial sugerida é Entrevista.
                        </p>
                    @endif
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 via-white to-indigo-50/80 shadow-sm">
                <div class="border-b border-violet-100/80 bg-white/60 px-6 py-6 sm:px-8">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                        <div class="shrink-0 rounded-2xl bg-gradient-to-br from-violet-500/20 to-indigo-600/20 p-1 shadow-md ring-4 ring-white">
                            <x-graca-avatar size="lg" class="ring-0 shadow-sm" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">A sua guia na trilha</p>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ config('career_trail.mentor_label', 'Sra. Graça') }}</h1>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                @if (trim((string) ($currentStep->graca_guidance ?? '')) !== '')
                                    {{ $currentStep->graca_guidance }}
                                @else
                                    Psicóloga, coaching e aconselhadora de carreira. Vou acompanhar cada etapa com calma e objetivos claros —
                                    começamos pelo essencial e avançamos no seu ritmo.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-6 sm:px-8">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Etapas e assistentes</h2>
                    <p class="mt-1 text-xs text-slate-500">Em cada passo encontram-se o objetivo da etapa e, quando configurado, o assistente correspondente. Desbloqueia ao atingir essa etapa na ordem da trilha (ou antes, se já tiver ultrapassado).</p>

                    <ol class="mt-6 space-y-3">
                        @foreach ($steps as $step)
                            @php
                                $isCurrent = (int) $step->id === (int) $currentStep->id;
                                $isUnlocked = (int) $step->sort_order <= $maxReached;
                                $stepAgent = $step->resolvedAgent();
                                $stepTrailDone = $isUnlocked && \App\Support\CareerTrailStepCompletion::bannerShowsCompletedBadge($trailMapUser, $step, $currentStep);
                            @endphp
                            <li>
                                <div
                                    @class([
                                        'flex gap-4 rounded-xl border p-4 transition',
                                        'border-violet-400 bg-violet-50/90 shadow-sm ring-1 ring-violet-200' => $isCurrent,
                                        'border-emerald-200 bg-emerald-50/50' => $isUnlocked && ! $isCurrent,
                                        'border-slate-200 bg-slate-50/70' => ! $isUnlocked,
                                    ])
                                >
                                    <div @class([
                                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold tabular-nums',
                                        'bg-violet-600 text-white' => $isCurrent,
                                        'bg-emerald-500 text-white' => $isUnlocked && ! $isCurrent,
                                        'border border-slate-300 bg-white text-slate-400' => ! $isUnlocked,
                                    ])>
                                        @if ($stepTrailDone)
                                            <span aria-hidden="true">✓</span>
                                        @else
                                            {{ $step->sort_order }}
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1 space-y-3">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="font-semibold text-slate-900">{{ $step->title }}</h3>
                                                @if ($isCurrent)
                                                    <span class="rounded-full bg-violet-600 px-2 py-0.5 text-xs font-medium text-white">Etapa atual</span>
                                                @elseif ($isUnlocked)
                                                    <span class="rounded-full border border-emerald-300 bg-white px-2 py-0.5 text-xs font-medium text-emerald-800">Assistente disponível</span>
                                                @else
                                                    <span class="rounded-full border border-slate-300 bg-white px-2 py-0.5 text-xs font-medium text-slate-600">Bloqueado</span>
                                                @endif
                                            </div>
                                            @if ($step->short_description)
                                                <p class="mt-1 text-sm text-slate-600">{{ $step->short_description }}</p>
                                            @endif
                                        </div>

                                        @if (! $isUnlocked)
                                            @if ($stepAgent)
                                                <p class="text-xs text-slate-500">Avance na trilha até esta etapa para usar o assistente <span class="font-medium text-slate-700">{{ $stepAgent->name }}</span>.</p>
                                            @elseif ($step->slug !== 'cv')
                                                <p class="text-xs text-slate-500">Assistente desta etapa ainda não está associado (configure na administração ou via variáveis de ambiente).</p>
                                            @endif
                                        @else
                                            @php
                                                $stepTrailUrl = $step->trailChatUrl();
                                                $stepPrimaryLabel = match ($step->slug) {
                                                    'cv' => 'CV completo',
                                                    'ats' => 'Hub ATS',
                                                    'cover-letter' => 'Manter',
                                                    'interviews' => 'Manter',
                                                    default => 'Abrir etapa',
                                                };
                                            @endphp
                                            @if ($step->slug === 'cv' && $stepTrailUrl)
                                                <div class="space-y-2 rounded-lg border border-emerald-200/70 bg-emerald-50/30 p-3">
                                                    <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-900/75">Este passo</p>
                                                    <div class="flex flex-wrap gap-2">
                                                        <a href="{{ $stepTrailUrl }}"
                                                           class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">
                                                            CV completo
                                                        </a>
                                                        @if ($cvCreatorChatUrl ?? null)
                                                            <a href="{{ $cvCreatorChatUrl }}"
                                                               class="inline-flex items-center rounded-lg border border-emerald-700 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-950 shadow-sm hover:bg-emerald-50">
                                                                CV Creator
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            @elseif ($step->slug === 'ats' && $stepTrailUrl)
                                                @php $atsInline = $stepAgent; @endphp
                                                <div class="space-y-2 rounded-lg border border-emerald-200/70 bg-emerald-50/30 p-3">
                                                    <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-900/75">Este passo</p>
                                                    <div class="flex flex-wrap gap-2">
                                                        <a href="{{ route('career-trail.ats') }}"
                                                           class="inline-flex items-center rounded-lg border border-emerald-600 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-950 shadow-sm hover:bg-emerald-50">
                                                            Hub ATS
                                                        </a>
                                                        @if ($atsInline && $atsInline->is_active)
                                                            <a href="{{ route('agents.documents.index', $atsInline) }}"
                                                               class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">
                                                                CV e JD
                                                            </a>
                                                            <a href="{{ route('agents.chat', $atsInline) }}"
                                                               class="inline-flex items-center rounded-lg border border-emerald-700 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-950 shadow-sm hover:bg-emerald-50">
                                                                ATS check
                                                            </a>
                                                        @elseif ($atsInline && ! $atsInline->is_active)
                                                            <span class="text-xs text-amber-800">Assistente ATS inativo.</span>
                                                        @else
                                                            <span class="text-xs text-slate-600">Configure o agente ATS na administração.</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @elseif ($step->slug === 'cover-letter')
                                                <div class="space-y-2 rounded-lg border border-emerald-200/70 bg-emerald-50/30 p-3">
                                                    <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-900/75">Este passo</p>
                                                    @if ($stepTrailUrl && $stepAgent && $stepAgent->is_active)
                                                        <div class="flex flex-wrap gap-2">
                                                            <a href="{{ $stepTrailUrl }}"
                                                               class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">
                                                                Manter
                                                            </a>
                                                            <a href="{{ route('agents.chat', $stepAgent) }}"
                                                               class="inline-flex items-center rounded-lg border border-emerald-700 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-950 shadow-sm hover:bg-emerald-50">
                                                                Nova
                                                            </a>
                                                        </div>
                                                    @elseif ($stepAgent && ! $stepAgent->is_active)
                                                        <p class="text-xs text-amber-800">Assistente de motivação inativo.</p>
                                                    @else
                                                        <p class="text-xs text-slate-600">Configure o agente desta etapa na administração.</p>
                                                    @endif
                                                </div>
                                            @elseif ($step->slug === 'interviews')
                                                <div class="space-y-2 rounded-lg border border-emerald-200/70 bg-emerald-50/30 p-3">
                                                    <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-900/75">Este passo</p>
                                                    @if ($stepTrailUrl && $stepAgent && $stepAgent->is_active)
                                                        <div class="flex flex-wrap gap-2">
                                                            <a href="{{ $stepTrailUrl }}"
                                                               class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">
                                                                Manter
                                                            </a>
                                                            <a href="{{ route('agents.chat', $stepAgent) }}"
                                                               class="inline-flex items-center rounded-lg border border-emerald-700 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-950 shadow-sm hover:bg-emerald-50">
                                                                Preparar
                                                            </a>
                                                        </div>
                                                    @elseif ($stepAgent && ! $stepAgent->is_active)
                                                        <p class="text-xs text-amber-800">Assistente de entrevistas inativo.</p>
                                                    @else
                                                        <p class="text-xs text-slate-600">Configure o agente desta etapa na administração.</p>
                                                    @endif
                                                </div>
                                            @elseif ($stepTrailUrl)
                                                <div class="flex flex-wrap gap-2">
                                                    <a href="{{ $stepTrailUrl }}"
                                                       class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">
                                                        {{ $stepPrimaryLabel }}
                                                    </a>

                                                    @if ($stepAgent && $stepAgent->is_active && $step->slug !== 'ats' && $step->slug !== 'cover-letter' && $step->slug !== 'interviews')
                                                        <a href="{{ route('agents.chat', $stepAgent) }}"
                                                           class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                                                            Abrir chat
                                                        </a>
                                                        @unless ($stepAgent->isChatKitWorkflow())
                                                            <a href="{{ route('agents.documents.index', $stepAgent) }}"
                                                               class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 shadow-sm hover:bg-slate-50">
                                                                Biblioteca CV/JD
                                                            </a>
                                                        @endunless
                                                    @endif
                                                </div>
                                            @elseif ($stepAgent && ! $stepAgent->is_active)
                                                <p class="text-xs text-amber-800">O assistente desta etapa está inativo. Contacte o suporte se precisar de ajuda.</p>
                                            @elseif ($step->slug !== 'cv')
                                                <p class="text-xs text-slate-500">Assistente desta etapa ainda não está associado (configure na administração ou via variáveis de ambiente).</p>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    @if ($currentStep->short_description)
                        <div class="mt-8 rounded-xl border border-indigo-100 bg-indigo-50/40 p-5">
                            <h3 class="text-sm font-semibold text-indigo-950">Foco nesta etapa</h3>
                            <p class="mt-2 text-sm text-indigo-950/90">{{ $currentStep->short_description }}</p>
                            @php
                                $focusTrailUrl = (int) $currentStep->sort_order <= $maxReached ? $currentStep->trailChatUrl() : null;
                                $focusPrimaryLabel = match ($currentStep->slug) {
                                    'cv' => 'CV completo',
                                    'ats' => 'Hub ATS',
                                    'cover-letter' => 'Manter',
                                    'interviews' => 'Manter',
                                    default => 'Abrir etapa',
                                };
                            @endphp
                            @if ($focusTrailUrl || $currentStep->slug === 'ats' || $currentStep->slug === 'cover-letter' || $currentStep->slug === 'interviews')
                                <div class="mt-4 flex flex-wrap gap-3">
                                    @if ($currentStep->slug === 'cv' && ($cvCreatorChatUrl ?? null))
                                        <a href="{{ $focusTrailUrl }}"
                                           class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                            {{ $focusPrimaryLabel }}
                                        </a>
                                        <a href="{{ $cvCreatorChatUrl }}"
                                           class="inline-flex items-center rounded-lg border border-indigo-300 bg-white px-4 py-2 text-sm font-medium text-indigo-950 shadow-sm hover:bg-indigo-50">
                                            CV Creator
                                        </a>
                                    @elseif ($currentStep->slug === 'cv')
                                        <a href="{{ $focusTrailUrl }}"
                                           class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                            {{ $focusPrimaryLabel }}
                                        </a>
                                    @elseif ($currentStep->slug === 'ats')
                                        @if ($focusTrailUrl)
                                            <a href="{{ route('career-trail.ats') }}"
                                               class="inline-flex items-center rounded-lg border border-indigo-300 bg-white px-4 py-2 text-sm font-medium text-indigo-950 shadow-sm hover:bg-indigo-50">
                                                Hub ATS
                                            </a>
                                        @endif
                                        @if (($atsStepAgent ?? null) && $atsStepAgent->is_active)
                                            <a href="{{ route('agents.documents.index', $atsStepAgent) }}"
                                               class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                                CV e JD
                                            </a>
                                            <a href="{{ route('agents.chat', $atsStepAgent) }}"
                                               class="inline-flex items-center rounded-lg border border-indigo-300 bg-white px-4 py-2 text-sm font-medium text-indigo-950 shadow-sm hover:bg-indigo-50">
                                                ATS check
                                            </a>
                                        @endif
                                        <a href="{{ route('career-trail.cv') }}"
                                           class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                                            Meu CV
                                        </a>
                                    @elseif ($currentStep->slug === 'cover-letter')
                                        @if ($focusTrailUrl && ($coverLetterStepAgent ?? null) && $coverLetterStepAgent->is_active)
                                            <a href="{{ $focusTrailUrl }}"
                                               class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                                Manter
                                            </a>
                                            <a href="{{ route('agents.chat', $coverLetterStepAgent) }}"
                                               class="inline-flex items-center rounded-lg border border-indigo-300 bg-white px-4 py-2 text-sm font-medium text-indigo-950 shadow-sm hover:bg-indigo-50">
                                                Nova
                                            </a>
                                        @endif
                                    @elseif ($currentStep->slug === 'interviews')
                                        @if ($focusTrailUrl && ($interviewStepAgent ?? null) && $interviewStepAgent->is_active)
                                            <a href="{{ $focusTrailUrl }}"
                                               class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                                Manter
                                            </a>
                                            <a href="{{ route('agents.chat', $interviewStepAgent) }}"
                                               class="inline-flex items-center rounded-lg border border-indigo-300 bg-white px-4 py-2 text-sm font-medium text-indigo-950 shadow-sm hover:bg-indigo-50">
                                                Preparar
                                            </a>
                                        @endif
                                    @else
                                        @if ($focusTrailUrl)
                                            <a href="{{ $focusTrailUrl }}"
                                               class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                                {{ $focusPrimaryLabel }}
                                            </a>
                                            <a href="{{ route('career-trail.cv') }}"
                                               class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                                                Meu CV
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    @php
                        $hasNextStepTrail = $currentStep->sort_order < $steps->max('sort_order');
                        $mayAdvanceTrail = ($currentStepReadiness['ready'] ?? true);
                    @endphp
                    <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-slate-200/80 pt-6">
                        <form method="POST" action="{{ route('career-trail.back') }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                    @disabled($currentStep->sort_order <= $steps->min('sort_order'))>
                                Etapa anterior
                            </button>
                        </form>
                        <form method="POST" action="{{ route('career-trail.advance') }}">
                            @csrf
                            <button type="submit"
                                    title="{{ ! $mayAdvanceTrail && $hasNextStepTrail ? ($currentStepReadiness['blocked_message'] ?? '') : '' }}"
                                    class="inline-flex items-center rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-40"
                                    @disabled(! $hasNextStepTrail || ! $mayAdvanceTrail)>
                                Avançar etapa
                            </button>
                        </form>
                        <p class="w-full text-xs text-slate-500 sm:w-auto sm:flex-1">
                            O «Avançar» só prossegue quando os requisitos da etapa atual estão cumpridos (passos 1 e 2). Voltar atrás não remove o que já desbloqueou.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
