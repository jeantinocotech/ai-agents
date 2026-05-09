<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    @php
        /** @var array{progress:\App\Models\UserCareerTrailProgress,steps:\Illuminate\Support\Collection,current:\App\Models\CareerTrailStep} $bundle */
        $progress = $bundle['progress'];
        $steps = $bundle['steps'];
        $current = $bundle['current'];
        $maxReached = (int) ($progress->max_sort_order_reached ?? $current->sort_order);
        $totalSteps = max(1, (int) $steps->count());
        $unlockedSteps = (int) $steps->filter(fn ($s) => (int) $s->sort_order <= $maxReached)->count();
        $pct = (int) round(($unlockedSteps / $totalSteps) * 100);
        $rankTitle = $snapshot->rank?->title ?? '—';
        $scoreTotal = (int) ($snapshot->score_total ?? 0);
        $badges = (array) ($snapshot->badges_state ?? []);
    @endphp

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 overflow-hidden rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 via-white to-indigo-50/80 shadow-sm">
                    <div class="border-b border-violet-100/80 bg-white/60 px-6 py-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Resumo</p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">A sua trilha</h1>
                        <p class="mt-2 text-sm text-slate-600">
                            Etapa atual: <strong class="text-slate-900">{{ $current->title }}</strong>
                        </p>
                    </div>
                    <div class="px-6 py-6">
                        <div class="flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Progresso</p>
                                <p class="mt-1 text-sm text-slate-700">
                                    {{ $unlockedSteps }} de {{ $totalSteps }} etapas desbloqueadas
                                </p>
                            </div>
                            <a href="{{ route('career-trail.index') }}"
                               class="inline-flex items-center rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">
                                Abrir trilha
                            </a>
                        </div>
                        <div class="mt-4 h-3 w-full rounded-full bg-slate-100">
                            <div class="h-3 rounded-full bg-violet-600" style="width: {{ $pct }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ $pct }}%</p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rank</p>
                        <div class="mt-1 flex items-baseline justify-between gap-3">
                            <h2 class="flex items-center gap-2 text-xl font-bold text-slate-900">
                                @if (! empty($snapshot->rank?->icon_key))
                                    <span class="text-2xl leading-none" aria-hidden="true">{{ $snapshot->rank->icon_key }}</span>
                                @endif
                                <span>{{ $rankTitle }}</span>
                            </h2>
                            <span class="text-sm font-semibold text-slate-700 tabular-nums">{{ number_format($scoreTotal, 0, ',', '.') }} pts</span>
                        </div>
                    </div>
                    <div class="px-6 py-6">
                        <p class="text-sm text-slate-600">
                            O score cresce com o seu uso da plataforma e marcos importantes (como aprovação).
                        </p>
                        @if (auth()->user()?->isAdmin())
                            <a href="{{ route('admin.gamification.index') }}" class="mt-4 inline-flex text-xs font-semibold text-indigo-700 hover:underline">
                                (admin) Ajustar gamificação
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tokens</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900">Uso recente</h3>
                    </div>
                    <div class="px-6 py-6 space-y-2 text-sm text-slate-700">
                        <p><span class="text-slate-500">Últimos 7 dias:</span> <strong class="tabular-nums">{{ number_format((int) ($tokenStats['used_7d'] ?? 0), 0, ',', '.') }}</strong></p>
                        <p><span class="text-slate-500">Últimos 30 dias:</span> <strong class="tabular-nums">{{ number_format((int) ($tokenStats['used_30d'] ?? 0), 0, ',', '.') }}</strong></p>
                        <div class="pt-2">
                            <a href="{{ route('tokens.purchase') }}" class="inline-flex items-center rounded-xl bg-teal-500 px-4 py-2 text-sm font-semibold text-black hover:bg-teal-400">
                                Comprar tokens
                            </a>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Badges da trilha</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900">Níveis por etapa</h3>
                    </div>
                    <div class="px-6 py-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach (['cv','ats','motivation','interviews'] as $key)
                            @php
                                $b = $badges[$key] ?? null;
                            @endphp
                            <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $b['label'] ?? strtoupper($key) }}</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900">
                                            @if (!empty($b['current']))
                                                {{ $b['current']['title'] }} · <span class="tabular-nums">{{ number_format((int) ($b['count'] ?? 0), 0, ',', '.') }}</span>
                                            @else
                                                Sem badge · <span class="tabular-nums">{{ number_format((int) ($b['count'] ?? 0), 0, ',', '.') }}</span>
                                            @endif
                                        </p>
                                        @if (!empty($b['next']))
                                            <p class="mt-1 text-xs text-slate-500">
                                                Próximo: {{ $b['next']['title'] }} ({{ $b['next']['threshold'] }})
                                            </p>
                                        @endif
                                    </div>
                                    @if (! empty($b['current']['icon_key']))
                                        <span class="text-2xl leading-none select-none" title="Badge" aria-hidden="true">{{ $b['current']['icon_key'] }}</span>
                                    @endif
                                </div>
                                @php
                                    $count = (int) ($b['count'] ?? 0);
                                    $prev = (int) ($b['current']['threshold'] ?? 0);
                                    $nextT = (int) ($b['next']['threshold'] ?? 0);
                                    $span = max(1, $nextT - $prev);
                                    $prog = $nextT > 0 ? min(100, (int) round((($count - $prev) / $span) * 100)) : 100;
                                @endphp
                                <div class="mt-3 h-2 w-full rounded-full bg-white">
                                    <div class="h-2 rounded-full bg-indigo-600" style="width: {{ $prog }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Aprovações</p>
                    <div class="mt-1 flex items-baseline justify-between gap-3">
                        <h3 class="text-lg font-semibold text-slate-900">Grandes marcos</h3>
                        <span class="text-sm font-semibold text-slate-700 tabular-nums">Total: {{ number_format((int) $approvedTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="px-6 py-6">
                    @if (($approvedLatest ?? collect())->isEmpty())
                        <p class="text-sm text-slate-600">Sem aprovações marcadas ainda. Quando marcar “Aprovado” num processo, ele aparece aqui.</p>
                    @else
                        <ul class="space-y-3">
                            @foreach ($approvedLatest as $proc)
                                <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-emerald-100 bg-emerald-50/50 px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-emerald-950">
                                            {{ $proc->jdDocument?->title ?: ('Vaga #'.$proc->jd_document_id) }}
                                        </p>
                                        <p class="text-xs text-emerald-900/70">Marcado em {{ $proc->updated_at?->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <a href="{{ route('agents.interview-preparations.index', $proc->jdDocument?->agent_id ?? 0) }}"
                                       class="text-xs font-semibold text-emerald-900 hover:underline">
                                        Ver entrevistas
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

