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
                /** @var \App\Models\User $trailMapUser — alinhamento com badge da trilha suspensa */
                $trailMapUser = auth()->user();
            @endphp

            <div class="overflow-hidden rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 via-white to-indigo-50/80 shadow-sm">
                <x-graca-orientation-panel
                    :page-key="\App\Support\GracaPanelPreferences::PAGE_TRAIL_MAP"
                    wrapper-class="mb-0 rounded-none border-0 bg-transparent shadow-none"
                    border-class="border-0 bg-transparent"
                >
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                        <div class="shrink-0 rounded-2xl bg-gradient-to-br from-violet-500/20 to-indigo-600/20 p-1 shadow-md ring-4 ring-white">
                            <x-graca-avatar size="lg" class="ring-0 shadow-sm" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">A sua guia na trilha</p>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ config('career_trail.mentor_label', 'Sra. Graça') }}</h1>
                            <x-graca-slot
                                :placement="\App\Support\CareerTrailGracaSlots::TRAIL_STEP_HEADER"
                                :step="null"
                                paragraph-class="mt-2 text-sm leading-relaxed text-slate-600"
                                :fallback="'Psicóloga, coaching e aconselhadora de carreira. Vou acompanhar cada etapa com calma e objetivos claros — começamos pelo essencial e avançamos no seu ritmo.'"
                            />
                        </div>
                    </div>
                </x-graca-orientation-panel>

                <div class="border-t border-violet-100/80 px-6 py-6 sm:px-8">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Etapas e assistentes</h2>
                    <p class="mt-1 text-xs text-slate-500">Cada passo desbloqueia ao cumprir o anterior. Depois de desbloqueado, pode navegar livremente entre as etapas disponíveis.</p>

                    <ol class="mt-6 space-y-3">
                        @foreach ($steps as $step)
                            @php
                                $isUnlocked = (int) $step->sort_order <= $maxReached;
                                $stepAgent = $step->resolvedAgent();
                                $stepTrailDone = $isUnlocked && \App\Support\CareerTrailStepCompletion::bannerShowsCompletedBadge($trailMapUser, $step);
                            @endphp
                            <li>
                                <div
                                    @class([
                                        'flex gap-4 rounded-xl border p-4 transition',
                                        'border-emerald-200 bg-emerald-50/50' => $isUnlocked,
                                        'border-slate-200 bg-slate-50/70' => ! $isUnlocked,
                                    ])
                                >
                                    <div @class([
                                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold tabular-nums',
                                        'bg-emerald-500 text-white' => $isUnlocked,
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
                                                @if ($stepTrailDone)
                                                    <span class="rounded-full border border-emerald-300 bg-white px-2 py-0.5 text-xs font-medium text-emerald-800">Concluído</span>
                                                @elseif ($isUnlocked)
                                                    <span class="rounded-full border border-emerald-300 bg-white px-2 py-0.5 text-xs font-medium text-emerald-800">Disponível</span>
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
                                                            Upload CV
                                                        </a>
                                                        @if ($cvCreatorChatUrl ?? null)
                                                            <a href="{{ $cvCreatorChatUrl }}"
                                                               class="inline-flex items-center rounded-lg border border-emerald-700 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-950 shadow-sm hover:bg-emerald-50">
                                                                Ajustar/Criar CV
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
                                                           class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">
                                                            CV+Vagas
                                                        </a>
                                                        @if ($atsInline && $atsInline->is_active && ($atsAllowsCheck ?? false))
                                                            <a href="{{ route('agents.chat', $atsInline) }}"
                                                               class="inline-flex items-center rounded-lg border border-emerald-700 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-950 shadow-sm hover:bg-emerald-50">
                                                                Passar no Filtro
                                                            </a>
                                                        @elseif ($atsInline && ! $atsInline->is_active)
                                                            <span class="text-xs text-amber-800">Assistente ATS inativo.</span>
                                                        @elseif (! $atsInline)
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


                </div>
            </div>
        </div>
    </div>
</x-app-layout>
