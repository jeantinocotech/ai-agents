@props(['context'])

@php
    /** @var \Illuminate\Support\Collection $steps */
    $steps = $context['steps'];
    $current = $context['current'];
    $gracaBody = $context['gracaBody'] ?? '';
    $suggestAdvance = (bool) ($context['suggestAdvance'] ?? false);
    $advanceReason = $context['advanceReason'] ?? null;
    $nextStepTitle = $context['nextStepTitle'] ?? null;
@endphp

<div class="border-b border-violet-200/80 bg-gradient-to-r from-violet-50 via-white to-indigo-50/90 shadow-sm">
    <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-stretch lg:gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-violet-700">Trilha de carreira</span>
                    <a href="{{ route('career-trail.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                        Ver mapa completo
                    </a>
                </div>
                <p class="mt-1 text-sm font-semibold text-slate-900">
                    Etapa {{ $current->sort_order }} — {{ $current->title }}
                </p>
                <div class="mt-2 flex flex-wrap gap-1.5" aria-label="Progresso na trilha">
                    @foreach ($steps as $step)
                        @php
                            $isCurrent = (int) $step->id === (int) $current->id;
                            $isPast = $step->sort_order < $current->sort_order;
                        @endphp
                        <span
                            @class([
                                'inline-flex h-7 min-w-[1.75rem] items-center justify-center rounded-full px-2 text-xs font-bold',
                                'bg-violet-600 text-white ring-2 ring-violet-300' => $isCurrent,
                                'bg-emerald-500 text-white' => $isPast && ! $isCurrent,
                                'border border-slate-300 bg-white text-slate-400' => ! $isPast && ! $isCurrent,
                            ])
                            title="{{ $step->title }}">
                            @if ($isPast && ! $isCurrent)
                                <span aria-hidden="true">✓</span>
                            @else
                                {{ $step->sort_order }}
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="flex shrink-0 gap-3 rounded-xl border border-violet-100 bg-white/90 p-3 shadow-sm ring-1 ring-violet-100/60 lg:max-w-md">
                <x-graca-avatar size="md" class="ring-violet-100" />
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-violet-800">{{ config('career_trail.mentor_label', 'Sra. Graça') }}</p>
                    @if ($gracaBody !== '')
                        <p class="mt-1 text-xs leading-relaxed text-slate-700">{{ $gracaBody }}</p>
                    @endif
                    @if ($suggestAdvance && $nextStepTitle)
                        <div class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50/80 px-2 py-2">
                            <p class="text-xs font-medium text-emerald-950">{{ $advanceReason }}</p>
                            <form method="post" action="{{ route('career-trail.advance') }}" class="mt-2">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-emerald-800 underline decoration-emerald-400 underline-offset-2 hover:text-emerald-950">
                                    Avançar para «{{ $nextStepTitle }}»
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
