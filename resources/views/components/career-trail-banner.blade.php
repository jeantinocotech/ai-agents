@props(['context'])

@php
    /** @var \Illuminate\Support\Collection<\App\Models\CareerTrailStep> $steps */
    $steps = $context['steps'];
    /** @var \App\Models\CareerTrailStep $current */
    $current = $context['current'];
    /** @var \App\Models\User $trailBannerUser */
    $trailBannerUser = $context['user'];
    $maxReached = (int) ($context['maxReached'] ?? $current->sort_order);
    $tokenBalance = (int) ($context['tokenBalance'] ?? 0);
    $cvCreatorChatUrl = $context['cvCreatorChatUrl'] ?? null;
    /** @var \App\Models\Agent|null $atsBannerAgent */
    $atsBannerAgent = $context['atsStepAgent'] ?? null;
    $atsAllowsCheckBanner = (bool) ($context['atsAllowsCheck'] ?? false);
@endphp

<div class="sticky top-16 z-40 border-b border-violet-200/80 bg-gradient-to-r from-violet-50/95 via-white/95 to-indigo-50/95 shadow-sm backdrop-blur-sm">
    <div class="mx-auto max-w-7xl px-4 py-1.5 sm:px-6 lg:px-8">
        <div class="-mx-1 flex flex-wrap items-center gap-x-2 gap-y-1.5 pb-1.5 sm:gap-x-3 sm:pb-0">
            <div class="flex min-w-0 shrink-0 items-center gap-1 px-1 sm:gap-1.5">
                <span class="whitespace-nowrap text-[10px] font-semibold uppercase tracking-wide text-violet-700 sm:text-[11px]">Trilha</span>
                <a href="{{ route('career-trail.index') }}"
                   @class([
                       'whitespace-nowrap rounded px-1.5 py-0.5 text-[10px] font-medium hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 sm:px-2 sm:text-[11px]',
                       'bg-violet-100 text-violet-900 ring-1 ring-violet-200' => request()->routeIs('career-trail.index') || request()->routeIs('career-trail.advance') || request()->routeIs('career-trail.back'),
                       'text-indigo-600 hover:text-indigo-800' => ! request()->routeIs('career-trail.index') && ! request()->routeIs('career-trail.advance') && ! request()->routeIs('career-trail.back'),
                   ])>
                    Mapa
                </a>
                <span class="hidden text-slate-300 sm:inline" aria-hidden="true">|</span>
            </div>

            <div class="flex min-w-0 flex-1 flex-wrap items-stretch gap-2 px-1"
                 role="navigation"
                 aria-label="Passos da trilha">
                @foreach ($steps as $step)
                    @php
                        $stepAgent = $step->resolvedAgent();
                        $isUnlocked = (int) $step->sort_order <= $maxReached;
                        $isCurrent = (int) $step->id === (int) $current->id;
                        $trailUrl = $isUnlocked ? $step->trailChatUrl() : null;
                        $showCheck = $isUnlocked && \App\Support\CareerTrailStepCompletion::bannerShowsCompletedBadge($trailBannerUser, $step, $current);
                        $bundleRing = $isCurrent
                            ? 'border-violet-400 bg-violet-50/95 ring-1 ring-violet-200 shadow-sm'
                            : ($isUnlocked ? 'border-emerald-200/90 bg-emerald-50/50 ring-1 ring-emerald-100/70' : 'border-dashed border-slate-300 bg-slate-50/60 opacity-90');
                    @endphp

                    @if ($step->slug === 'cv')
                        <div @class(['flex min-w-[8.5rem] shrink-0 flex-col gap-1 rounded-lg px-2 py-1.5', $bundleRing])>
                            <div class="flex items-center gap-1.5 border-b border-slate-200/60 pb-1">
                                <span @class([
                                    'inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-[10px] font-bold leading-none tabular-nums',
                                    'bg-violet-600 text-white' => $isCurrent,
                                    'bg-emerald-500 text-white' => $isUnlocked && ! $isCurrent,
                                    'border border-slate-300 bg-white text-slate-400' => ! $isUnlocked,
                                ])>
                                    @if ($showCheck)
                                        <span aria-hidden="true">✓</span>
                                    @else
                                        {{ $step->sort_order }}
                                    @endif
                                </span>
                                <span class="text-[11px] font-bold text-slate-800">{{ $step->trailBarLinkLabel() }}</span>
                            </div>
                            @if ($isUnlocked && $trailUrl)
                                <div class="flex flex-col gap-1 sm:flex-row sm:flex-wrap">
                                    <a href="{{ route('career-trail.cv') }}"
                                       class="inline-flex flex-1 items-center justify-center rounded-md bg-emerald-600 px-2 py-1 text-center text-[10px] font-semibold leading-tight text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 sm:flex-initial">
                                        CV Formulário
                                    </a>
                                    @if ($cvCreatorChatUrl)
                                        <a href="{{ $cvCreatorChatUrl }}"
                                           title="CV Creator"
                                           class="inline-flex flex-1 items-center justify-center rounded-md border border-emerald-700 bg-white px-2 py-1 text-center text-[10px] font-semibold leading-tight text-emerald-950 hover:bg-emerald-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 sm:flex-initial">
                                            CV Assistente
                                        </a>
                                    @endif
                                </div>
                            @else
                                <p class="text-[9px] leading-snug text-slate-400">Complete o passo anterior.</p>
                            @endif
                        </div>

                    @elseif ($step->slug === 'ats')
                        @php
                            $atsA = $stepAgent ?? $atsBannerAgent;
                            $atsLibsOk = $isUnlocked && $trailUrl && $atsA !== null && $atsA->is_active;
                        @endphp
                        <div @class(['flex min-w-[10rem] shrink-0 flex-col gap-1 rounded-lg px-2 py-1.5', $bundleRing])>
                            @if ($isUnlocked && $trailUrl)
                                <a href="{{ route('career-trail.ats') }}"
                                   class="flex items-center gap-1.5 border-b border-slate-200/60 pb-1 text-left hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-300 focus-visible:ring-offset-1"
                                   aria-label="Resumo da etapa ATS (hub)">
                                    <span @class([
                                        'inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-[10px] font-bold tabular-nums leading-none',
                                        'bg-violet-600 text-white' => $isCurrent,
                                        'bg-emerald-500 text-white' => $isUnlocked && ! $isCurrent,
                                    ])>
                                        @if ($showCheck)
                                            <span aria-hidden="true">✓</span>
                                        @else
                                            {{ $step->sort_order }}
                                        @endif
                                    </span>
                                    <span class="text-[11px] font-bold text-slate-800">{{ $step->trailBarLinkLabel() }}</span>
                                </a>
                            @else
                                <div class="flex items-center gap-1.5 border-b border-slate-200/60 pb-1">
                                    <span @class([
                                        'inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border border-slate-300 bg-white px-1 text-[10px] font-bold tabular-nums leading-none text-slate-400',
                                    ])>
                                        {{ $step->sort_order }}
                                    </span>
                                    <span class="text-[11px] font-bold text-slate-500">{{ $step->trailBarLinkLabel() }}</span>
                                </div>
                            @endif
                            @if ($atsLibsOk)
                                <div class="flex flex-col gap-1 sm:flex-row sm:flex-wrap">
                                    <a href="{{ route('career-trail.ats') }}"
                                       title="CV, vagas e ATS na trilha"
                                       class="inline-flex flex-1 items-center justify-center rounded-md bg-emerald-600 px-2 py-1 text-center text-[10px] font-semibold leading-tight text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 sm:flex-initial">
                                        CV / JD
                                    </a>
                                    @if ($atsAllowsCheckBanner)
                                        <a href="{{ route('agents.chat', $atsA) }}"
                                           title="ATS check"
                                           class="inline-flex flex-1 items-center justify-center rounded-md border border-emerald-700 bg-white px-2 py-1 text-center text-[10px] font-semibold leading-tight text-emerald-950 hover:bg-emerald-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 sm:flex-initial">
                                            ATS Filtro
                                        </a>
                                    @endif
                                </div>
                            @elseif ($isUnlocked && (! $atsA || ! $atsA->is_active))
                                <p class="text-[9px] leading-snug text-amber-800/90">Assistente ATS em configuração.</p>
                            @elseif (! $isUnlocked)
                                <p class="text-[9px] leading-snug text-slate-400">Complete o passo anterior.</p>
                            @endif
                        </div>

                    @elseif ($step->slug === 'cover-letter')
                        @php
                            $clAgent = $stepAgent;
                            $coverLibsOk = $isUnlocked && $trailUrl && $clAgent !== null && $clAgent->is_active;
                        @endphp
                        <div @class(['flex min-w-[8.5rem] shrink-0 flex-col gap-1 rounded-lg px-2 py-1.5', $bundleRing])>
                            <div class="flex items-center gap-1.5 border-b border-slate-200/60 pb-1">
                                <span @class([
                                    'inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-[10px] font-bold leading-none tabular-nums',
                                    'bg-violet-600 text-white' => $isCurrent,
                                    'bg-emerald-500 text-white' => $isUnlocked && ! $isCurrent,
                                    'border border-slate-300 bg-white text-slate-400' => ! $isUnlocked,
                                ])>
                                    @if ($showCheck)
                                        <span aria-hidden="true">✓</span>
                                    @else
                                        {{ $step->sort_order }}
                                    @endif
                                </span>
                                <span class="text-[11px] font-bold text-slate-800">{{ $step->trailBarLinkLabel() }}</span>
                            </div>
                            @if ($coverLibsOk)
                                <div class="flex flex-col gap-1 sm:flex-row sm:flex-wrap">
                                    <a href="{{ $trailUrl }}"
                                       title="Biblioteca de cartas"
                                       class="inline-flex flex-1 items-center justify-center rounded-md bg-emerald-600 px-2 py-1 text-center text-[10px] font-semibold leading-tight text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 sm:flex-initial">
                                        Cartas
                                    </a>
                                    <a href="{{ route('agents.chat', $clAgent) }}"
                                       title="Nova carta (chat)"
                                       class="inline-flex flex-1 items-center justify-center rounded-md border border-emerald-700 bg-white px-2 py-1 text-center text-[10px] font-semibold leading-tight text-emerald-950 hover:bg-emerald-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 sm:flex-initial">
                                        Criar Carta
                                    </a>
                                </div>
                            @elseif ($isUnlocked && (! $clAgent || ! $clAgent->is_active))
                                <p class="text-[9px] leading-snug text-amber-800/90">Assistente Motivação em configuração.</p>
                            @elseif (! $isUnlocked)
                                <p class="text-[9px] leading-snug text-slate-400">Complete o passo anterior.</p>
                            @endif
                        </div>

                    @elseif ($step->slug === 'interviews')
                        @php
                            $eqAgent = $stepAgent;
                            $interviewLibsOk = $isUnlocked && $trailUrl && $eqAgent !== null && $eqAgent->is_active;
                        @endphp
                        <div @class(['flex min-w-[8.5rem] shrink-0 flex-col gap-1 rounded-lg px-2 py-1.5', $bundleRing])>
                            <div class="flex items-center gap-1.5 border-b border-slate-200/60 pb-1">
                                <span @class([
                                    'inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-[10px] font-bold leading-none tabular-nums',
                                    'bg-violet-600 text-white' => $isCurrent,
                                    'bg-emerald-500 text-white' => $isUnlocked && ! $isCurrent,
                                    'border border-slate-300 bg-white text-slate-400' => ! $isUnlocked,
                                ])>
                                    @if ($showCheck)
                                        <span aria-hidden="true">✓</span>
                                    @else
                                        {{ $step->sort_order }}
                                    @endif
                                </span>
                                <span class="text-[11px] font-bold text-slate-800">{{ $step->trailBarLinkLabel() }}</span>
                            </div>
                            @if ($interviewLibsOk)
                                <div class="flex flex-col gap-1 sm:flex-row sm:flex-wrap">
                                    <a href="{{ $trailUrl }}"
                                       title="Registros de entrevistas"
                                       class="inline-flex flex-1 items-center justify-center rounded-md bg-emerald-600 px-2 py-1 text-center text-[10px] font-semibold leading-tight text-white hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 sm:flex-initial">
                                        Histórico
                                    </a>
                                    <a href="{{ route('agents.chat', $eqAgent) }}"
                                       title="Preparar entrevistas (chat)"
                                       class="inline-flex flex-1 items-center justify-center rounded-md border border-emerald-700 bg-white px-2 py-1 text-center text-[10px] font-semibold leading-tight text-emerald-950 hover:bg-emerald-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 sm:flex-initial">
                                        Preparar
                                    </a>
                                </div>
                            @elseif ($isUnlocked && (! $eqAgent || ! $eqAgent->is_active))
                                <p class="text-[9px] leading-snug text-amber-800/90">Assistente Entrevistas em configuração.</p>
                            @elseif (! $isUnlocked)
                                <p class="text-[9px] leading-snug text-slate-400">Complete o passo anterior.</p>
                            @endif
                        </div>

                    @else
                        {{-- Passos seguintes: chip único (+ ícones chat/biblioteca se aplicável) --}}
                        <div class="flex shrink-0 items-center gap-0.5">
                            @if ($isUnlocked && $trailUrl)
                                <a href="{{ $trailUrl }}"
                                   title="{{ $step->title }}"
                                   @class([
                                       'inline-flex min-h-[1.375rem] shrink-0 items-center justify-center gap-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 sm:min-h-6 sm:px-2 sm:text-[11px]',
                                       'bg-violet-600 text-white ring-2 ring-violet-300 focus-visible:ring-violet-500' => $isCurrent,
                                       'bg-emerald-500 text-white hover:bg-emerald-600 focus-visible:ring-emerald-400' => ! $isCurrent,
                                   ])>
                                    @if ($showCheck)
                                        <span aria-hidden="true">✓</span>
                                    @else
                                        <span>{{ $step->sort_order }}</span>
                                    @endif
                                    <span class="whitespace-nowrap font-semibold">{{ $step->trailBarLinkLabel() }}</span>
                                </a>
                            @else
                                <span title="{{ ! $isUnlocked ? ($stepAgent ? 'Complete etapas anteriores para desbloquear' : $step->title) : '' }}"
                                      @class([
                                          'inline-flex min-h-[1.375rem] shrink-0 items-center justify-center gap-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none sm:min-h-6 sm:px-2 sm:text-[11px]',
                                          'border border-dashed border-slate-300 bg-white/70 text-slate-400 opacity-85' => ! $isUnlocked,
                                          'bg-violet-100 text-violet-900 ring-1 ring-violet-300' => $isCurrent && ! $trailUrl,
                                      ])>
                                    @if ($showCheck)
                                        <span aria-hidden="true">✓</span>
                                    @else
                                        <span>{{ $step->sort_order }}</span>
                                    @endif
                                    <span class="whitespace-nowrap font-semibold">{{ $step->trailBarLinkLabel() }}</span>
                                </span>
                            @endif

                            @if ($isUnlocked && $trailUrl && $stepAgent && $stepAgent->is_active && $step->slug !== 'interviews')
                                <a href="{{ route('agents.chat', $stepAgent) }}"
                                   title="Chat: {{ $stepAgent->name }}"
                                   class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
                                   aria-label="Abrir chat do passo {{ $step->sort_order }}">
                                    <i class="fas fa-comments text-[10px]" aria-hidden="true"></i>
                                </a>
                                @unless ($stepAgent->isChatKitWorkflow())
                                    <a href="{{ route('agents.documents.index', $stepAgent) }}"
                                       title="Biblioteca de documentos"
                                       class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
                                       aria-label="Biblioteca do passo {{ $step->sort_order }}">
                                        <i class="fas fa-folder-open text-[10px]" aria-hidden="true"></i>
                                    </a>
                                @endunless
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="flex w-full shrink-0 items-center gap-2 border-t border-violet-100/70 pt-2 sm:ml-auto sm:w-auto sm:justify-end sm:border-l sm:border-t-0 sm:border-violet-200/80 sm:pl-2 sm:pt-0">
                <div class="min-w-0 leading-tight">
                    <p class="text-[9px] font-semibold text-slate-500">Tokens</p>
                    <p class="truncate text-sm font-bold tabular-nums text-emerald-800" data-live-token-balance>{{ number_format($tokenBalance, 0, ',', '.') }}</p>
                </div>
                <a href="{{ route('tokens.purchase') }}"
                   aria-label="Comprar tokens"
                   @class([
                       'inline-flex shrink-0 items-center justify-center rounded-md bg-indigo-600 px-2 py-1 text-[10px] font-semibold text-white shadow-sm hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 sm:px-2.5 sm:text-[11px]',
                       'ring-2 ring-indigo-400 ring-offset-1' => request()->routeIs('tokens.purchase'),
                   ])>
                    + Token
                </a>
            </div>
        </div>
    </div>
</div>
