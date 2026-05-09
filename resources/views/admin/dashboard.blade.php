<x-app-layout>
    <div class="py-10 bg-slate-50 min-h-[calc(100vh-8rem)]">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- Cabeçalho + período --}}
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Administração</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Visão geral</h1>
                    <p class="mt-2 text-sm text-slate-600 max-w-xl">
                        Utilização da plataforma, fluxo de tokens, novos utilizadores e gamificação.
                        Comparativo no período selecionado.
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-1 shadow-sm inline-flex flex-wrap gap-1 self-start">
                    @foreach ($periodOptions as $pKey => $pLabel)
                        <a href="{{ route('admin.dashboard', ['period' => $pKey]) }}"
                           class="whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold transition {{ $period['key'] === $pKey
                               ? 'bg-indigo-600 text-white shadow'
                               : 'text-slate-700 hover:bg-slate-50' }}">
                            {{ $pLabel }}
                        </a>
                    @endforeach
                </div>
            </div>

            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {{ session('success') }}
                </div>
            @endif

            {{-- KPIs --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Novos utilizadores</p>
                    <p class="mt-3 text-3xl font-bold tabular-nums text-slate-900">{{ number_format($kpis['new_users'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-slate-600">Total registado na plataforma: <strong class="tabular-nums">{{ number_format($kpis['registered_users_total'], 0, ',', '.') }}</strong></p>
                </div>
                <div class="rounded-2xl border border-sky-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Utilizadores ativos</p>
                    <p class="mt-3 text-3xl font-bold tabular-nums text-slate-900">{{ number_format($kpis['distinct_active_users'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-slate-600">Com sessão, movimento de tokens ou evento de gamificação no período.</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Sessões de chat</p>
                    <p class="mt-3 text-3xl font-bold tabular-nums text-slate-900">{{ number_format($kpis['chat_sessions'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-slate-600">Novas sessões criadas no período.</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800">Tokens</p>
                    <p class="mt-2 text-sm text-slate-600">Consumo <span class="font-semibold text-slate-900 tabular-nums">{{ number_format($kpis['tokens_consumed_total'], 0, ',', '.') }}</span></p>
                    <p class="text-sm text-slate-600">Créditos <span class="font-semibold text-slate-900 tabular-nums">{{ number_format($kpis['tokens_credit_total'], 0, ',', '.') }}</span></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Compras de pacotes</p>
                    <p class="mt-3 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($kpis['purchases_count'], 0, ',', '.') }}</p>
                    <p class="mt-2 text-xs text-slate-600 space-y-0.5">
                        <span class="block">Volume de tokens vendidos: <strong class="tabular-nums">{{ number_format($kpis['purchases_tokens_volume'], 0, ',', '.') }}</strong></span>
                        <span class="block">Receita (BRL): <strong class="tabular-nums">{{ number_format($kpis['purchases_revenue_brl'], 2, ',', '.') }}</strong></span>
                    </p>
                </div>
                <div class="rounded-2xl border border-indigo-100 bg-indigo-50/40 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-800">Gamificação</p>
                    <p class="mt-3 text-2xl font-bold tabular-nums text-slate-900">{{ number_format($kpis['gamification_event_count'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-slate-600">Eventos registados · Pontos estimados (definições atuais): <strong class="tabular-nums">{{ number_format($kpis['gamification_points_estimate'], 0, ',', '.') }}</strong></p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col justify-center">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Atalhos</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('admin.agents.index') }}" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">Agentes</a>
                        <a href="{{ route('admin.settings.tokens.edit') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-50">Tokens</a>
                        <a href="{{ route('admin.gamification.index') }}" class="inline-flex items-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-900 hover:bg-indigo-100">Gamificação</a>
                        <a href="{{ route('admin.testimonials.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-50">Depoimentos</a>
                        <a href="{{ route('admin.career-trail-steps.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-50">Trilha</a>
                    </div>
                </div>
            </div>

            {{-- Gráficos --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-slate-900">Utilização</h2>
                        <p class="text-xs text-slate-500">{{ $period['label'] }} · sessões de chat vs. novos utilizadores por {{ $period['bucket'] === 'month' ? 'mês' : 'dia' }}.</p>
                    </div>
                    <canvas id="chartUsage" class="max-h-[280px] w-full" height="220"></canvas>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-slate-900">Tokens</h2>
                        <p class="text-xs text-slate-500">Consumo vs. créditos por {{ $period['bucket'] === 'month' ? 'mês' : 'dia' }}.</p>
                    </div>
                    <canvas id="chartTokens" class="max-h-[280px] w-full" height="220"></canvas>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-slate-900">Eventos de gamificação</h2>
                    <p class="text-xs text-slate-500">Volume temporal no período selecionado.</p>
                </div>
                <canvas id="chartGame" class="max-h-[240px] w-full" height="180"></canvas>
            </div>

            {{-- Tabelas gamificação --}}
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h2 class="text-base font-semibold text-slate-900">Distribuição por tipo de evento</h2>
                        <p class="text-xs text-slate-500">Até 20 categorias mais frequentes neste período.</p>
                    </div>
                    <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 text-left">Evento</th>
                                    <th class="px-4 py-3 text-right">Ocorrências</th>
                                    <th class="px-4 py-3 text-right">Pts /evt</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($gamification_breakdown as $row)
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-slate-900">{{ $row->label }}</span>
                                            <span class="block text-[11px] text-slate-400 font-mono">{{ $row->event_key }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row->event_count, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums text-slate-600">{{ $row->points_per_event !== null ? number_format((int) $row->points_per_event, 0, ',', '.') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-slate-500">Sem eventos no período.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h2 class="text-base font-semibold text-slate-900">Atividade recente</h2>
                        <p class="text-xs text-slate-500">Últimos 25 eventos no período.</p>
                    </div>
                    <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 text-left">Quando</th>
                                    <th class="px-4 py-3 text-left">Utilizador</th>
                                    <th class="px-4 py-3 text-left">Evento</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($recent_gamification_events as $ev)
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $ev->occurred_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="text-slate-900">{{ Str::limit($ev->user?->name ?? '—', 32) }}</span>
                                            @if ($ev->user?->email)
                                                <span class="block text-[11px] text-slate-400">{{ Str::limit($ev->user->email, 36) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $ev->event_key }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-slate-500">Sem eventos recentes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    (function () {
        var labels = @json($charts['activity']['labels']);
        var sess = @json($charts['activity']['sessions']);
        var users = @json($charts['activity']['new_users']);

        var tLabels = @json($charts['tokens']['labels']);
        var tCons = @json($charts['tokens']['consumption']);
        var tIn = @json($charts['tokens']['incoming']);

        var gLabels = @json($charts['gamification']['labels']);
        var gEv = @json($charts['gamification']['events']);

        function baseOpts() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            };
        }

        var uCtx = document.getElementById('chartUsage');
        if (uCtx) {
            new Chart(uCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Sessões de chat',
                            data: sess,
                            borderColor: 'rgb(14, 165, 233)',
                            backgroundColor: 'rgba(14, 165, 233, 0.12)',
                            fill: true,
                            tension: 0.35
                        },
                        {
                            label: 'Novos utilizadores',
                            data: users,
                            borderColor: 'rgb(124, 58, 237)',
                            backgroundColor: 'rgba(124, 58, 237, 0.06)',
                            fill: true,
                            tension: 0.35
                        }
                    ]
                },
                options: Object.assign(baseOpts(), {
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                })
            });
        }

        var tokCtx = document.getElementById('chartTokens');
        if (tokCtx) {
            new Chart(tokCtx, {
                type: 'bar',
                data: {
                    labels: tLabels,
                    datasets: [
                        {
                            label: 'Consumo',
                            data: tCons,
                            backgroundColor: 'rgba(239, 68, 68, 0.65)'
                        },
                        {
                            label: 'Créditos',
                            data: tIn,
                            backgroundColor: 'rgba(16, 185, 129, 0.65)'
                        }
                    ]
                },
                options: Object.assign(baseOpts(), {
                    scales: {
                        x: { stacked: false },
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                })
            });
        }

        var gCtx = document.getElementById('chartGame');
        if (gCtx) {
            new Chart(gCtx, {
                type: 'bar',
                data: {
                    labels: gLabels,
                    datasets: [{
                        label: 'Eventos de gamificação',
                        data: gEv,
                        backgroundColor: 'rgba(99, 102, 241, 0.55)'
                    }]
                },
                options: Object.assign(baseOpts(), {
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                })
            });
        }
    })();
</script>
