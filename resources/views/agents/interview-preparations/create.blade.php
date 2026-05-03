<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nova entrada de entrevista · {{ $agent->name }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 text-sm sm:px-0">
                <a href="{{ route('agents.interview-preparations.index', $agent) }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">&larr; Registros</a>
                <span class="text-slate-300">·</span>
                <a href="{{ route('agents.chat', $agent) }}" class="font-medium text-violet-700 hover:text-violet-950 hover:underline">Preparar no chat</a>
                <span class="text-slate-300">·</span>
                <a href="{{ $documentsHubUrl }}" class="font-medium text-emerald-700 hover:text-emerald-950 hover:underline">Biblioteca ATS</a>
            </div>

            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl">
                <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-5">
                    <h1 class="text-lg font-semibold text-slate-900">Registrar entrevista</h1>
                    <p class="mt-1 text-sm text-slate-600">Cada linha corresponde a uma ronda (sequência 1, 2, 3…) no mesmo processo (vaga + CV).</p>
                </div>

                <div class="px-6 py-6">
                    @if ($allProcessesClosedForNewEntry ?? false)
                        <p class="text-sm text-amber-900">
                            Todos os processos na biblioteca estão marcados como <strong>"Não prosseguiu"</strong> no resultado global da candidatura.
                            Não é possível registrar novas rondas enquanto assim estiverem.
                            Se quiser continuar a acompanhar esse processo, altere o resultado global na <a href="{{ route('agents.interview-preparations.index', $agent) }}" class="font-medium text-indigo-600 hover:underline">lista de entrevistas</a>.
                        </p>
                    @elseif ($jdOptions === [])
                        <p class="text-sm text-amber-900">Sem JDs navegáveis. Configure na <a href="{{ $documentsHubUrl }}" class="font-medium text-indigo-600 hover:underline">biblioteca ATS</a>.</p>
                    @else
                        <form method="post" action="{{ route('agents.interview-preparations.store', $agent) }}" class="space-y-5">
                            @csrf
                            <div>
                                <label for="jd_document_id" class="block text-sm font-medium text-slate-700">Processo (vaga ATS)</label>
                                <select id="jd_document_id" name="jd_document_id" required
                                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="" disabled @selected($preselectJd === null)>— Selecionar —</option>
                                    @foreach ($jdOptions as $opt)
                                        <option value="{{ $opt['id'] }}"
                                            @selected((string) $opt['id'] === (string) $preselectJd)>
                                            {{ $opt['title'] }}
                                            @if (! empty($opt['paired_cv_label']))
                                                · {{ $opt['paired_cv_label'] }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @if (($didNotProceedProcessHiddenCount ?? 0) > 0)
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $didNotProceedProcessHiddenCount === 1
                                            ? 'Um processo com candidatura global "Não prosseguiu" não aparece nesta lista.'
                                            : $didNotProceedProcessHiddenCount.' processos com candidatura global "Não prosseguiu" não aparecem nesta lista.' }}
                                    </p>
                                @endif
                                @error('jd_document_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm ring-1 ring-inset ring-slate-100">
                                <p id="next-round-preview" class="font-medium text-slate-800">Selecione um processo para ver a próxima ronda.</p>
                                <p class="mt-1 text-xs text-slate-500">A sequência corresponde à ronda (1, 2, 3…). É sempre a próxima livre neste processo e é definida ao salvar — não pode ser alterada manualmente aqui.</p>
                            </div>
                            <div>
                                <label for="persona" class="block text-sm font-medium text-slate-700">Persona</label>
                                <select id="persona" name="persona" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="" disabled {{ old('persona') ? '' : 'selected' }}>— Escolher —</option>
                                    @foreach ($personaOptions as $opt)
                                        <option value="{{ $opt['value'] }}" @selected(old('persona') === $opt['value'])>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('persona')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-slate-700">Estado do processo (após esta ronda)</label>
                                <select id="status" name="status" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="" disabled {{ old('status') ? '' : 'selected' }}>— Escolher —</option>
                                    @foreach ($statusOptions as $opt)
                                        <option value="{{ $opt['value'] }}" @selected(old('status') === $opt['value'])>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="chat_prep_messages" class="block text-sm font-medium text-slate-700">Principais mensagens da preparação (chat)</label>
                                <p class="mt-1 text-xs text-slate-500">O que você pode extrair das sugestões e do diálogo com o agente no chat antes ou depois da ronda.</p>
                                <textarea id="chat_prep_messages" name="chat_prep_messages" rows="8"
                                          class="mt-1 block w-full rounded-lg border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('chat_prep_messages') }}</textarea>
                                @error('chat_prep_messages')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="learnings" class="block text-sm font-medium text-slate-700">Principais aprendizados</label>
                                <p class="mt-1 text-xs text-slate-500">Resumo útil como histórico do processo; no futuro pode servir de dicas personalizadas.</p>
                                <textarea id="learnings" name="learnings" rows="8"
                                          class="mt-1 block w-full rounded-lg border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('learnings') }}</textarea>
                                @error('learnings')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="submit" class="inline-flex rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-700">Salvar</button>
                                <a href="{{ route('agents.interview-preparations.index', $agent) }}" class="inline-flex rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
                            </div>
                        </form>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var select = document.getElementById('jd_document_id');
                                var hint = document.getElementById('next-round-preview');
                                if (!select || !hint) return;
                                var map = @json($nextRoundByJdId);
                                function refresh() {
                                    var id = select.value;
                                    if (!id) {
                                        hint.textContent = 'Selecione um processo para ver a próxima ronda.';
                                        return;
                                    }
                                    var n = map[id];
                                    if (n === undefined) n = map[String(id)];
                                    if (n === undefined) n = map[Number(id)];
                                    hint.textContent = (n !== undefined)
                                        ? 'Será registrada como ronda ' + n + '.'
                                        : 'Não foi possível determinar a próxima ronda.';
                                }
                                select.addEventListener('change', refresh);
                                refresh();
                            });
                        </script>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
