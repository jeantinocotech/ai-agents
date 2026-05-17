<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Ajustar CV — {{ $analysis->jdDocument?->title ?: 'Vaga' }}
            </h2>
            <a href="{{ route('career-trail.ats') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                &larr; Vagas
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                    {{ session('status') }}
                </div>
            @endif
            @if (request()->query('synced') === '1')
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                    Tabela actualizada com a última análise do ChatKit.
                </div>
            @endif

            @if ($analysis->source !== \App\Models\AtsAnalysis::SOURCE_CHATKIT_TOOL)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="alert">
                    Esta lista foi gerada automaticamente pela app e pode não coincidir com a tabela do ChatKit.
                    @if ($reanalyzeUrl)
                        <a href="{{ $reanalyzeUrl }}" class="font-semibold text-amber-950 underline hover:text-amber-800">Abrir «Passar no filtro» no chat</a>
                        e aguarde a mensagem «Tabela ATS guardada» — o workspace será actualizado com o ATS % oficial.
                    @else
                        Volte ao chat ATS, conclua a análise e aguarde a mensagem «Tabela ATS guardada» antes de ajustar o CV aqui.
                    @endif
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-slate-100 pb-3">
                    <div>
                        @if ($analysis->ats_score !== null)
                            <p class="text-2xl font-bold tabular-nums text-indigo-700">
                                ATS {{ number_format((float) $analysis->ats_score, 0) }}%
                            </p>
                            @if ($analysis->previous_ats_score !== null)
                                <p class="text-xs text-slate-600">
                                    Antes: {{ number_format((float) $analysis->previous_ats_score, 0) }}%
                                    → Agora: {{ number_format((float) $analysis->ats_score, 0) }}%
                                </p>
                            @endif
                        @else
                            <p class="text-sm font-medium text-slate-700">Análise preparada — execute «Passar no filtro» no chat para obter o ATS %.</p>
                        @endif
                        <p class="mt-1 text-sm text-slate-600">
                            CV: <strong>{{ $analysis->userCv?->title ?: '—' }}</strong>
                            · Vaga: <strong>{{ $analysis->jdDocument?->title ?: '—' }}</strong>
                            @if ($analysis->updated_at)
                                <span class="text-slate-400">· Actualizada {{ $analysis->updated_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                            @endif
                        </p>
                    </div>
                    <p class="text-sm font-semibold text-slate-800">
                        <span id="ats-addressed-count">{{ $addressedCount }}</span> / {{ $items->count() }} keywords tratadas
                    </p>
                </div>

                <div class="mt-4 flex flex-wrap gap-2 text-xs">
                    <label class="inline-flex items-center gap-1.5">
                        <input type="checkbox" id="ats-filter-pending" class="rounded border-slate-300 text-indigo-600" checked>
                        Só pendentes
                    </label>
                    <x-ui.button type="button" id="ats-toggle-table" variant="ghost" size="xs">
                        Ver tabela completa
                    </x-ui.button>
                </div>

                <div id="ats-focus-panel" class="mt-4 rounded-xl border border-indigo-100 bg-indigo-50/40 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-800">Em foco</p>
                    <h3 id="ats-focus-keyword" class="mt-1 text-lg font-bold text-slate-900">—</h3>
                    <p id="ats-focus-meta" class="mt-1 text-sm text-slate-600"></p>
                    <p id="ats-focus-snippet" class="mt-2 text-sm text-slate-700 empty:hidden"></p>
                    <p id="ats-focus-suggestion" class="mt-2 rounded-lg bg-white/80 px-3 py-2 text-sm text-slate-800 empty:hidden"></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <x-ui.button type="button" id="ats-copy-suggestion" variant="ghost" size="xs" class="hidden">
                            Copiar sugestão
                        </x-ui.button>
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-emerald-200 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-800">
                            <input type="checkbox" id="ats-focus-addressed" class="rounded border-slate-300 text-emerald-600">
                            Tratei no CV
                        </label>
                        <x-ui.button type="button" id="ats-prev-item" variant="ghost" size="xs">← Anterior</x-ui.button>
                        <x-ui.button type="button" id="ats-next-item" variant="ghost" size="xs">Próxima →</x-ui.button>
                    </div>
                </div>

                <div id="ats-full-table" class="mt-4 hidden overflow-x-auto">
                    <table class="min-w-full text-left text-xs">
                        <thead class="border-b border-slate-200 text-slate-600">
                            <tr>
                                <th class="px-2 py-2">Keyword</th>
                                <th class="px-2 py-2">Estado</th>
                                <th class="px-2 py-2">Relevância</th>
                                <th class="px-2 py-2">Tratado</th>
                            </tr>
                        </thead>
                        <tbody id="ats-table-body" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>

            <form method="post" action="{{ route('career-trail.ats.workspace.cv', $analysis) }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                @csrf
                @method('PUT')
                <label for="cv_body" class="block text-sm font-semibold text-slate-900">Editor do CV</label>
                <textarea id="cv_body" name="body" rows="16" required maxlength="{{ $maxCvBodyChars }}"
                          class="mt-2 w-full rounded-xl border-slate-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('body', $analysis->userCv?->body) }}</textarea>
                <div class="mt-4 flex flex-wrap gap-3">
                    <x-ui.button type="submit" name="redirect" value="workspace" variant="primary" size="md">
                        Salvar
                    </x-ui.button>
                    @if ($reanalyzeUrl)
                        <x-ui.button type="submit" name="redirect" value="reanalyze" variant="outline" size="md">
                            Salvar e passar no filtro
                        </x-ui.button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @php
        $itemsJson = $items->map(fn ($i) => [
            'id' => $i->id,
            'keyword' => $i->keyword,
            'relevance' => $i->relevance,
            'relevance_label' => $i->relevanceLabel(),
            'match_status' => $i->match_status,
            'match_label' => $i->matchStatusLabel(),
            'cv_snippet' => $i->cv_snippet,
            'suggestion' => $i->suggestion,
            'is_addressed' => $i->is_addressed,
            'priority_rank' => $i->priority_rank,
        ])->values();
    @endphp
    <script>
        (function () {
            var items = @json($itemsJson);
            var patchItemUrlTemplate = @json(route('career-trail.ats.workspace.item', ['item' => '__ITEM__']));
            var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            var focusIndex = 0;
            var filterPending = true;

            function visibleItems() {
                return items.filter(function (it) {
                    if (!filterPending) return true;
                    if (it.is_addressed) return false;
                    return it.match_status === 'missing' || it.match_status === 'partial';
                });
            }

            function currentList() {
                var list = visibleItems();
                if (list.length === 0) list = items.slice();
                return list;
            }

            function renderFocus() {
                var list = currentList();
                if (list.length === 0) {
                    document.getElementById('ats-focus-keyword').textContent = 'Nenhum item';
                    return;
                }
                if (focusIndex >= list.length) focusIndex = list.length - 1;
                if (focusIndex < 0) focusIndex = 0;
                var it = list[focusIndex];
                document.getElementById('ats-focus-keyword').textContent = it.keyword;
                document.getElementById('ats-focus-meta').textContent =
                    it.match_label + ' · Relevância ' + it.relevance_label;
                var sn = document.getElementById('ats-focus-snippet');
                sn.textContent = it.cv_snippet ? 'No CV: ' + it.cv_snippet : 'Ainda não aparece no CV.';
                sn.classList.toggle('hidden', !it.cv_snippet && it.match_status === 'missing');
                var sug = document.getElementById('ats-focus-suggestion');
                if (it.suggestion) {
                    sug.textContent = it.suggestion;
                    sug.classList.remove('hidden');
                    document.getElementById('ats-copy-suggestion').classList.remove('hidden');
                } else {
                    sug.textContent = '';
                    sug.classList.add('hidden');
                    document.getElementById('ats-copy-suggestion').classList.add('hidden');
                }
                document.getElementById('ats-focus-addressed').checked = !!it.is_addressed;
            }

            function renderTable() {
                var tbody = document.getElementById('ats-table-body');
                tbody.innerHTML = '';
                items.forEach(function (it, idx) {
                    var tr = document.createElement('tr');
                    tr.className = 'cursor-pointer hover:bg-slate-50';
                    tr.innerHTML =
                        '<td class="px-2 py-2 font-medium">' + it.keyword + '</td>' +
                        '<td class="px-2 py-2">' + it.match_label + '</td>' +
                        '<td class="px-2 py-2">' + it.relevance_label + '</td>' +
                        '<td class="px-2 py-2">' + (it.is_addressed ? '✓' : '—') + '</td>';
                    tr.addEventListener('click', function () {
                        focusIndex = currentList().findIndex(function (x) { return x.id === it.id; });
                        if (focusIndex < 0) focusIndex = idx;
                        renderFocus();
                    });
                    tbody.appendChild(tr);
                });
            }

            function patchAddressed(itemId, addressed) {
                return fetch(patchItemUrlTemplate.replace('__ITEM__', String(itemId)), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ is_addressed: addressed }),
                }).then(function (r) { return r.json(); });
            }

            function updateAddressedCount() {
                var n = items.filter(function (i) { return i.is_addressed; }).length;
                document.getElementById('ats-addressed-count').textContent = String(n);
            }

            document.getElementById('ats-filter-pending').addEventListener('change', function (e) {
                filterPending = e.target.checked;
                focusIndex = 0;
                renderFocus();
            });
            document.getElementById('ats-toggle-table').addEventListener('click', function () {
                var el = document.getElementById('ats-full-table');
                var hidden = el.classList.toggle('hidden');
                this.textContent = hidden ? 'Ver tabela completa' : 'Ocultar tabela';
            });
            document.getElementById('ats-prev-item').addEventListener('click', function () {
                focusIndex -= 1;
                renderFocus();
            });
            document.getElementById('ats-next-item').addEventListener('click', function () {
                focusIndex += 1;
                renderFocus();
            });
            document.getElementById('ats-focus-addressed').addEventListener('change', function (e) {
                var list = currentList();
                var it = list[focusIndex];
                if (!it) return;
                patchAddressed(it.id, e.target.checked).then(function () {
                    it.is_addressed = e.target.checked;
                    updateAddressedCount();
                    if (e.target.checked && filterPending) {
                        focusIndex = Math.min(focusIndex, Math.max(0, currentList().length - 1));
                    }
                    renderFocus();
                    renderTable();
                });
            });
            document.getElementById('ats-copy-suggestion').addEventListener('click', function () {
                var list = currentList();
                var it = list[focusIndex];
                if (it && it.suggestion && navigator.clipboard) {
                    navigator.clipboard.writeText(it.suggestion);
                }
            });

            renderTable();
            renderFocus();
        })();
    </script>
</x-app-layout>
