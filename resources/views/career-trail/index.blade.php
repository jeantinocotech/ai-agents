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

            <div class="mb-6 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Recursos sempre disponíveis</h2>
                <p class="mt-1 text-xs text-slate-600">
                    A ordem da trilha é uma sugestão de percurso — pode voltar a estes conteúdos a qualquer momento, em qualquer etapa.
                </p>
                <ul class="mt-3 flex flex-col gap-2 text-sm text-slate-700 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-6">
                    <li>
                        <a href="{{ route('career-trail.cv') }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">Meu CV</a>
                        <span class="text-slate-500"> — perfil, criador com assistente, copiar para agentes</span>
                    </li>
                    <li>
                        <a href="{{ route('dashboard') }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">Dashboard</a>
                        <span class="text-slate-500"> — em cada agente use <strong class="font-medium text-slate-600">Manter CV/JD</strong> para criar, <strong class="font-medium text-slate-600">editar</strong> ou apagar CVs e vagas (JD) na biblioteca</span>
                    </li>
                </ul>
            </div>

            @php
                $trailCta = match ($currentStep->slug) {
                    'cv' => ['url' => route('career-trail.cv'), 'label' => 'Abrir área do CV'],
                    default => ['url' => route('dashboard'), 'label' => 'Ir para os assistentes'],
                };
            @endphp
            <div class="mb-6 flex flex-col gap-3 rounded-xl border border-indigo-200 bg-indigo-50/70 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-indigo-950">Passo {{ $currentStep->sort_order }} — {{ $currentStep->title }}</p>
                    @if ($currentStep->short_description)
                        <p class="mt-0.5 text-xs text-indigo-900/80">{{ $currentStep->short_description }}</p>
                    @endif
                </div>
                <a href="{{ $trailCta['url'] }}"
                   class="inline-flex shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    {{ $trailCta['label'] }}
                </a>
            </div>

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
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Etapas da trilha</h2>
                    <p class="mt-1 text-xs text-slate-500">A etapa em destaque é a sugestão atual. As seguintes são o percurso recomendado — as ferramentas (CV, biblioteca, chats) continuam acessíveis quando quiser.</p>

                    <ol class="mt-6 space-y-3">
                        @foreach ($steps as $step)
                            @php
                                $isCurrent = (int) $step->id === (int) $currentStep->id;
                                $isPast = $step->sort_order < $currentStep->sort_order;
                                $isFuture = $step->sort_order > $currentStep->sort_order;
                            @endphp
                            <li>
                                <div
                                    @class([
                                        'flex gap-4 rounded-xl border p-4 transition',
                                        'border-violet-400 bg-violet-50/90 shadow-sm ring-1 ring-violet-200' => $isCurrent,
                                        'border-emerald-200 bg-emerald-50/50' => $isPast && ! $isCurrent,
                                        'border-slate-200 bg-slate-50/70' => $isFuture,
                                    ])
                                >
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold
                                        {{ $isCurrent ? 'bg-violet-600 text-white' : '' }}
                                        {{ $isPast && ! $isCurrent ? 'bg-emerald-500 text-white' : '' }}
                                        {{ $isFuture ? 'border border-slate-300 bg-white text-slate-600' : '' }}">
                                        @if ($isPast && ! $isCurrent)
                                            <span aria-hidden="true">✓</span>
                                        @else
                                            {{ $step->sort_order }}
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="font-semibold text-slate-900">{{ $step->title }}</h3>
                                            @if ($isCurrent)
                                                <span class="rounded-full bg-violet-600 px-2 py-0.5 text-xs font-medium text-white">Etapa atual</span>
                                            @elseif ($isFuture)
                                                <span class="rounded-full border border-slate-300 bg-white px-2 py-0.5 text-xs font-medium text-slate-600">A seguir</span>
                                            @else
                                                <span class="rounded-full border border-emerald-300 bg-white px-2 py-0.5 text-xs font-medium text-emerald-800">Concluída</span>
                                            @endif
                                        </div>
                                        @if ($step->short_description)
                                            <p class="mt-1 text-sm text-slate-600">{{ $step->short_description }}</p>
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
                            <div class="mt-4 flex flex-wrap gap-3">
                                <a href="{{ route('dashboard') }}"
                                   class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                                    Ir para os assistentes
                                </a>
                                <a href="{{ route('home') }}"
                                   class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                                    Ver agentes
                                </a>
                            </div>
                        </div>
                    @endif

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
                                    class="inline-flex items-center rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-40"
                                    @disabled($currentStep->sort_order >= $steps->max('sort_order'))>
                                Avançar etapa
                            </button>
                        </form>
                        <p class="w-full text-xs text-slate-500 sm:w-auto sm:flex-1">
                            Estes botões servem para navegar na trilha enquanto desenvolvemos critérios automáticos de conclusão em cada etapa.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
