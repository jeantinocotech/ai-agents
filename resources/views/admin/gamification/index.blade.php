<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gamificação
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-6 flex gap-2">
                <a href="{{ route('admin.gamification.index', ['tab' => 'badges']) }}"
                   class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold border {{ $tab === 'badges' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
                    Badges
                </a>
                <a href="{{ route('admin.gamification.index', ['tab' => 'score']) }}"
                   class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold border {{ $tab === 'score' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
                    Score &amp; Ranks
                </a>
            </div>

            @if ($tab === 'badges')
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <form method="POST" action="{{ route('admin.gamification.badges.update') }}" class="space-y-8">
                        @csrf
                        @method('PUT')

                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Badges da trilha</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                Configure níveis (thresholds), títulos e ícones. O dashboard exibe sempre: <strong>Etapa — Título · N</strong>.
                            </p>
                            <p class="mt-2 text-sm text-slate-600">
                                <strong>Ícones:</strong> o campo aceita texto mostrado tal qual na interface — o mais simples é colar um
                                <a href="https://emojipedia.org" target="_blank" rel="noopener noreferrer" class="font-semibold text-indigo-700 hover:underline">emoji do Emojipedia</a>
                                (ou usar o seletor do sistema, p.ex. macOS <kbd class="rounded border border-slate-300 bg-slate-100 px-1">Ctrl</kbd>+<kbd class="rounded border border-slate-300 bg-slate-100 px-1">⌘</kbd>+<kbd class="rounded border border-slate-300 bg-slate-100 px-1">Espaço</kbd>).
                                Deixe vazio para não mostrar ícone nesse nível.
                            </p>
                        </div>

                        @foreach ($badgeDefs as $defIdx => $def)
                            <div class="rounded-2xl border border-slate-200 p-5">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Badge</p>
                                        <h4 class="mt-1 text-base font-semibold text-slate-900">{{ $def->key }}</h4>
                                        <p class="mt-1 text-xs text-slate-500">ID: {{ $def->id }}</p>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 w-full sm:max-w-3xl">
                                        <input type="hidden" name="definitions[{{ $defIdx }}][id]" value="{{ $def->id }}">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-600">Label</label>
                                            <input name="definitions[{{ $defIdx }}][label]" value="{{ old("definitions.$defIdx.label", $def->label) }}"
                                                   class="mt-1 w-full rounded-lg border-slate-300 shadow-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-600">Label do contador</label>
                                            <input name="definitions[{{ $defIdx }}][counter_label]" value="{{ old("definitions.$defIdx.counter_label", $def->counter_label) }}"
                                                   class="mt-1 w-full rounded-lg border-slate-300 shadow-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-600">Ordem</label>
                                            <input type="number" name="definitions[{{ $defIdx }}][sort_order]" min="0"
                                                   value="{{ old("definitions.$defIdx.sort_order", $def->sort_order) }}"
                                                   class="mt-1 w-full rounded-lg border-slate-300 shadow-sm">
                                        </div>
                                        <div class="flex items-center gap-2 pt-6">
                                            <input type="checkbox" id="def-active-{{ $def->id }}" name="definitions[{{ $defIdx }}][is_active]" value="1"
                                                   @checked(old("definitions.$defIdx.is_active", $def->is_active))>
                                            <label for="def-active-{{ $def->id }}" class="text-sm text-slate-700">Ativo</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                                <th class="py-2 pr-3">Threshold</th>
                                                <th class="py-2 pr-3">Título</th>
                                                <th class="py-2 pr-3">Ícone (opcional)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($def->levels as $lvlIdx => $lvl)
                                                <tr>
                                                    <td class="py-2 pr-3">
                                                        <input type="hidden" name="levels[{{ $def->id }}_{{ $lvl->id }}][id]" value="{{ $lvl->id }}">
                                                        <input type="hidden" name="levels[{{ $def->id }}_{{ $lvl->id }}][badge_definition_id]" value="{{ $def->id }}">
                                                        <input type="number" min="1" name="levels[{{ $def->id }}_{{ $lvl->id }}][threshold]"
                                                               value="{{ old("levels.$def->id"."_$lvl->id.threshold", $lvl->threshold) }}"
                                                               class="w-28 rounded-lg border-slate-300 shadow-sm">
                                                    </td>
                                                    <td class="py-2 pr-3">
                                                        <input name="levels[{{ $def->id }}_{{ $lvl->id }}][title]"
                                                               value="{{ old("levels.$def->id"."_$lvl->id.title", $lvl->title) }}"
                                                               class="w-64 rounded-lg border-slate-300 shadow-sm">
                                                    </td>
                                                    <td class="py-2 pr-3">
                                                        <input name="levels[{{ $def->id }}_{{ $lvl->id }}][icon_key]"
                                                               value="{{ old("levels.$def->id"."_$lvl->id.icon_key", $lvl->icon_key) }}"
                                                               placeholder="ex.: 🎯"
                                                               class="w-64 rounded-lg border-slate-300 shadow-sm">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach

                        <div class="pt-2">
                            <x-ui.button type="submit" variant="primary" size="md">
                                Salvar badges
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <form method="POST" action="{{ route('admin.gamification.score.update') }}" class="space-y-10">
                        @csrf
                        @method('PUT')

                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Eventos de score</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                Pontos e caps diários são recalculados “lazy” no uso quando as definições mudam.
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                        <th class="py-2 pr-3">Key</th>
                                        <th class="py-2 pr-3">Label</th>
                                        <th class="py-2 pr-3">Pontos</th>
                                        <th class="py-2 pr-3">Cap diário</th>
                                        <th class="py-2 pr-3">Ativo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($eventDefs as $i => $ev)
                                        <tr>
                                            <td class="py-2 pr-3 font-mono text-xs text-slate-600">{{ $ev->key }}</td>
                                            <td class="py-2 pr-3">
                                                <input type="hidden" name="events[{{ $i }}][id]" value="{{ $ev->id }}">
                                                <input name="events[{{ $i }}][label]" value="{{ old("events.$i.label", $ev->label) }}"
                                                       class="w-72 rounded-lg border-slate-300 shadow-sm">
                                            </td>
                                            <td class="py-2 pr-3">
                                                <input type="number" name="events[{{ $i }}][points]" value="{{ old("events.$i.points", $ev->points) }}"
                                                       class="w-28 rounded-lg border-slate-300 shadow-sm">
                                            </td>
                                            <td class="py-2 pr-3">
                                                <input type="number" name="events[{{ $i }}][daily_cap]" value="{{ old("events.$i.daily_cap", $ev->daily_cap) }}"
                                                       placeholder="(sem)"
                                                       class="w-28 rounded-lg border-slate-300 shadow-sm">
                                            </td>
                                            <td class="py-2 pr-3">
                                                <input type="checkbox" name="events[{{ $i }}][is_active]" value="1" @checked(old("events.$i.is_active", $ev->is_active))>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Ranks</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                O rank é o maior <code>min_points</code> menor ou igual ao score total.
                                O campo <strong>Ícone</strong> segue a mesma regra dos badges (normalmente um emoji).
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                        <th class="py-2 pr-3">min_points</th>
                                        <th class="py-2 pr-3">Título</th>
                                        <th class="py-2 pr-3">Ícone</th>
                                        <th class="py-2 pr-3">Ativo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($ranks as $i => $r)
                                        <tr>
                                            <td class="py-2 pr-3">
                                                <input type="hidden" name="ranks[{{ $i }}][id]" value="{{ $r->id }}">
                                                <input type="number" name="ranks[{{ $i }}][min_points]" value="{{ old("ranks.$i.min_points", $r->min_points) }}"
                                                       class="w-32 rounded-lg border-slate-300 shadow-sm">
                                            </td>
                                            <td class="py-2 pr-3">
                                                <input name="ranks[{{ $i }}][title]" value="{{ old("ranks.$i.title", $r->title) }}"
                                                       class="w-64 rounded-lg border-slate-300 shadow-sm">
                                            </td>
                                            <td class="py-2 pr-3">
                                                <input name="ranks[{{ $i }}][icon_key]" value="{{ old("ranks.$i.icon_key", $r->icon_key) }}"
                                                       placeholder="ex.: ⭐"
                                                       class="w-24 rounded-lg border-slate-300 shadow-sm">
                                            </td>
                                            <td class="py-2 pr-3">
                                                <input type="checkbox" name="ranks[{{ $i }}][is_active]" value="1" @checked(old("ranks.$i.is_active", $r->is_active))>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="pt-2">
                            <x-ui.button type="submit" variant="primary" size="md">
                                Salvar score &amp; ranks
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

