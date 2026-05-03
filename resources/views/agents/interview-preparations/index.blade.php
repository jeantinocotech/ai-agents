<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Entrevistas · {{ $agent->name }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 text-sm sm:px-0">
                <a href="{{ route('career-trail.index') }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">&larr; Voltar à trilha</a>
                <span class="text-slate-300">·</span>
                <a href="{{ route('agents.chat', $agent) }}" class="font-medium text-violet-700 hover:text-violet-950 hover:underline">Preparar entrevistas (chat)</a>
                <span class="text-slate-300">·</span>
                <a href="{{ $documentsHubUrl }}" class="font-medium text-emerald-700 hover:text-emerald-950 hover:underline">Biblioteca ATS (CV e vagas)</a>
            </div>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            @php($searchInputCls = 'h-10 w-full rounded-lg border-slate-200 bg-white text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500')

            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl">
                <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/80 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h1 class="text-lg font-semibold text-slate-900">Registros de entrevistas</h1>
                        <p class="mt-1 text-sm text-slate-600">Agrupado por processo (vaga ATS + CV). Rondas listadas por sequência dentro de cada processo.</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <a href="{{ route('agents.interview-preparations.create', $agent) }}"
                           class="inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-violet-700">
                            Nova entrada
                        </a>
                        <a href="{{ route('agents.chat', $agent) }}"
                           class="inline-flex h-10 items-center justify-center rounded-xl border border-violet-500 bg-white px-4 text-sm font-semibold text-violet-950 shadow-sm hover:bg-violet-50">
                            Preparar (chat)
                        </a>
                    </div>
                </div>

                <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-4 sm:px-6">
                    <form method="get" action="{{ route('agents.interview-preparations.index', $agent) }}" id="interview-preps-filters" class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100">
                        <details id="interview-filters-drawer" class="group" {{ ! empty($filtersActive) ? 'open' : '' }}>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-left marker:content-none sm:px-5 sm:py-3.5 hover:bg-violet-50/35 [&::-webkit-details-marker]:hidden">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="text-sm font-semibold text-slate-900">Filtros</span>
                                        @if (! empty($filtersActive))
                                            <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-900">Em uso</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-xs leading-snug text-slate-500">
                                        Estado das <strong>rondas</strong>, <strong>resultado global da candidatura</strong> e pesquisa livre.
                                        <span class="group-open:hidden"> Clique nesta linha para expandir.</span>
                                        <span class="hidden group-open:inline"> Clique em Ocultar ou na linha para recolher.</span>
                                    </p>
                                    <p class="mt-1.5 text-[11px] leading-snug text-slate-400 group-open:hidden">
                                        Pré-seleção ao entrar: todas as personas · ronda "Em processo" + "Avançou" · candidatura "Em curso" + "Aprovado" (global).
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="hidden group-open:inline text-xs font-semibold text-violet-800 sm:inline">Ocultar</span>
                                    <span class="inline text-xs font-semibold text-violet-800 group-open:hidden sm:inline">Expandir</span>
                                    <svg class="iv-filter-chevron h-5 w-5 shrink-0 text-slate-500 transition-transform duration-200 group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </div>
                            </summary>

                            <div class="border-t border-slate-100 bg-white px-4 pb-5 pt-4 sm:px-5">
                        <div>
                            <label for="iv-q" class="block text-sm font-medium text-slate-700">Pesquisar</label>
                            <input id="iv-q" type="search" name="q" value="{{ $search }}" autocomplete="off"
                                   placeholder="Texto livre sobre notas das rondas, vaga…"
                                   class="{{ $searchInputCls }} mt-2 max-w-2xl" />
                        </div>

                        <div class="mt-6 grid gap-5 lg:grid-cols-3">
                            {{-- Personas --}}
                            <fieldset id="fld-personas" class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                                <legend class="w-full px-0.5 text-sm font-semibold text-slate-800">Persona da ronda</legend>
                                <p class="mt-1 px-0.5 text-xs text-slate-500">Nada marcado = todas.</p>
                                <div class="mt-3 max-h-[14rem] space-y-0.5 overflow-y-auto overscroll-contain pe-1 sm:max-h-none sm:overflow-visible">
                                    @foreach ($personaOptions as $opt)
                                        <label class="flex cursor-pointer items-start gap-2.5 rounded-lg px-2 py-2 hover:bg-violet-50/60 hover:ring-1 hover:ring-violet-100">
                                            <input type="checkbox" name="personas[]" value="{{ $opt['value'] }}"
                                                   class="mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                                   @checked(in_array($opt['value'], $personasSelected, true)) />
                                            <span class="text-sm leading-snug text-slate-700">{{ $opt['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            {{-- Estados da ronda --}}
                            <fieldset id="fld-statuses" class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                                <legend class="w-full px-0.5 text-sm font-semibold text-slate-800">Estado da ronda</legend>
                                <p class="mt-1 px-0.5 text-xs text-slate-500">
                                    Pré-selecionado: <strong>Em processo</strong> e <strong>Avançou</strong>. O interruptor abaixo inclui todos os estados.
                                </p>
                                <label for="iv-all-statuses" class="mt-3 flex cursor-pointer items-center gap-2 rounded-lg bg-white px-3 py-2.5 shadow-sm ring-1 ring-slate-200 hover:bg-violet-50/40">
                                    <input type="checkbox" name="all_statuses" id="iv-all-statuses" value="1"
                                           class="rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                           data-iv-collapse-target="fld-status-checkboxes"
                                           @checked($showAllStatusesCheckbox) />
                                    <span class="text-xs font-semibold leading-tight text-slate-700">Mostrar todas as situações de ronda</span>
                                </label>
                                <div id="fld-status-checkboxes" class="@if ($showAllStatusesCheckbox) pointer-events-none opacity-40 @endif mt-4 space-y-0.5">
                                    @foreach ($statusOptions as $opt)
                                        <label class="flex cursor-pointer items-start gap-2.5 rounded-lg px-2 py-2 hover:bg-violet-50/60 hover:ring-1 hover:ring-violet-100">
                                            <input type="checkbox" name="statuses[]" value="{{ $opt['value'] }}"
                                                   class="mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                                   @checked(in_array($opt['value'], $statusesSelected ?? $defaultStatusesValues, true)) />
                                            <span class="text-sm leading-snug text-slate-700">{{ $opt['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            {{-- Resultado CV/JD --}}
                            <fieldset id="fld-outcomes" class="rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                                <legend class="w-full px-0.5 text-sm font-semibold text-slate-800">Resultado da candidatura (global)</legend>
                                <p class="mt-1 px-0.5 text-xs text-slate-500">
                                    Pré-selecionado: <strong>Em curso</strong> e <strong>Aprovado</strong>. O interruptor abaixo inclui todos os resultados (ex.: "Não prosseguiu" global).
                                </p>
                                <label for="iv-all-process-outcomes" class="mt-3 flex cursor-pointer items-center gap-2 rounded-lg bg-white px-3 py-2.5 shadow-sm ring-1 ring-slate-200 hover:bg-violet-50/40">
                                    <input type="checkbox" name="all_process_outcomes" id="iv-all-process-outcomes" value="1"
                                           class="rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                           data-iv-collapse-target="fld-outcomes-checkboxes"
                                           @checked($showAllProcessOutcomesCheckbox) />
                                    <span class="text-xs font-semibold leading-tight text-slate-700">Incluir todos os resultados de candidatura</span>
                                </label>
                                <div id="fld-outcomes-checkboxes" class="@if ($showAllProcessOutcomesCheckbox) pointer-events-none opacity-40 @endif mt-4 space-y-0.5">
                                    @foreach ($processOutcomeOptions as $opt)
                                        <label class="flex cursor-pointer items-start gap-2.5 rounded-lg px-2 py-2 hover:bg-violet-50/60 hover:ring-1 hover:ring-violet-100">
                                            <input type="checkbox" name="process_outcomes[]" value="{{ $opt['value'] }}"
                                                   class="mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                                   @checked(in_array($opt['value'], $processOutcomesSelected ?? $defaultProcessOutcomesValues, true)) />
                                            <span class="text-sm leading-snug text-slate-700">{{ $opt['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        </div>
                            </div>
                        </details>

                        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-2.5 sm:px-5">
                            <button type="submit" class="inline-flex h-10 min-w-[7rem] items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-violet-700">
                                Aplicar
                            </button>
                            @if (! empty($filtersActive))
                                <a href="{{ route('agents.interview-preparations.index', $agent) }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                    Limpar
                                </a>
                            @endif
                        </div>
                    </form>
                    <script>
                        document.querySelectorAll('#interview-preps-filters input[data-iv-collapse-target]').forEach(function (master) {
                            var box = document.getElementById(master.getAttribute('data-iv-collapse-target'));
                            if (!box) return;
                            function refresh() {
                                var on = master.checked;
                                box.classList.toggle('pointer-events-none', on);
                                box.classList.toggle('opacity-40', on);
                            }
                            master.addEventListener('change', refresh);
                            refresh();
                        });
                    </script>
                </div>

                <div class="px-6 py-6">
                    @if ($jdOptions === [])
                        <p class="text-sm text-amber-900">Ainda não há vagas (JD) pareadas na biblioteca ATS. Abra a <a href="{{ $documentsHubUrl }}" class="font-medium text-indigo-600 hover:underline">biblioteca ATS</a>.</p>
                    @elseif ($processGroups->isEmpty())
                        <p class="text-sm text-slate-600">Sem registos com estes critérios. Crie uma <strong>Nova entrada</strong> ou use o <a href="{{ route('agents.chat', $agent) }}" class="font-medium text-indigo-600 hover:underline">chat</a> e depois registe aqui.</p>
                    @else
                        <ul class="space-y-8">
                            @foreach ($processGroups as $bundle)
                                @php($jd = $bundle['jd'] ?? null)
                                @php($proc = $bundle['process'] ?? null)
                                @php($Ongoing = \App\Enums\InterviewApplicationOutcome::Ongoing)
                                @php($Approved = \App\Enums\InterviewApplicationOutcome::Approved)
                                @php($DidNot = \App\Enums\InterviewApplicationOutcome::DidNotProceed)
                                <li class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50/40 ring-1 ring-slate-100">
                                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200/80 bg-white px-5 py-4">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Processo</p>
                                            <h2 class="mt-0.5 text-base font-semibold text-slate-900">
                                                @if ($jd)
                                                    {{ $jd->title ?: 'Vaga #'.$jd->id }}
                                                @else
                                                    JD #{{ ($bundle['rounds']->first()->jd_document_id ?? '?') }}
                                                @endif
                                            </h2>
                                            @if ($jd && $jd->relationLoaded('userCv') && $jd->userCv)
                                                <p class="mt-1 text-sm text-indigo-900">
                                                    <span class="text-slate-500">CV de perfil:</span>
                                                    <span class="font-medium">{{ $jd->userCv->title ?: '#'.$jd->userCv->id }}</span>
                                                </p>
                                            @endif
                                            @if ($proc instanceof \App\Models\InterviewProcess)
                                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                                    <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Estado candidatura</span>
                                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold
                                                        @switch($proc->outcome)
                                                            @case($Approved) bg-emerald-100 text-emerald-950 @break
                                                            @case($DidNot) bg-red-50 text-red-900 @break
                                                            @default bg-amber-50 text-amber-950
                                                        @endswitch
                                                    ">
                                                        {{ $proc->outcome->label() }}
                                                    </span>
                                                    <span class="text-xs text-slate-500">(O detalhe — não prosseguiu pela vaga ou desistência — aparece nas rondas.)</span>
                                                </div>
                                                @if ($jd)
                                                    @if ($proc->outcome !== $DidNot)
                                                        <div class="mt-3 flex flex-wrap gap-2">
                                                            @if ($proc->outcome !== $Approved)
                                                                <form method="post" action="{{ route('agents.interview-process.update', [$agent, $jd]) }}" class="inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="outcome" value="{{ $Approved->value }}" />
                                                                    <button type="submit"
                                                                            class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                                                        Marcar aprovado
                                                                    </button>
                                                                </form>
                                                                <p class="w-full basis-full text-[11px] text-slate-500">Ao aprovar, a etapa <strong>Proposta</strong> da trilha fica disponível ao avançar.</p>
                                                            @else
                                                                <form method="post" action="{{ route('agents.interview-process.update', [$agent, $jd]) }}" class="inline" onsubmit="return confirm('Repor candidatura como em curso?');">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="outcome" value="{{ $Ongoing->value }}" />
                                                                    <button type="submit"
                                                                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-50">
                                                                        Repor em curso (anular aprovação)
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <p class="mt-2 max-w-xl text-[11px] text-slate-600">Este processo ficou como "Não prosseguiu" no resultado global porque há pelo menos uma ronda terminada assim (pelo lado da vaga ou com desistência). Ajuste a ronda ou apague registros quando fizer sentido.</p>
                                                    @endif
                                                @endif
                                            @endif
                                        </div>
                                        @php($nRounds = count($bundle['rounds']))
                                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                            {{ $nRounds }} {{ $nRounds === 1 ? 'ronda' : 'rondas' }}
                                        </span>
                                    </div>
                                    <ul class="divide-y divide-slate-100 bg-white">
                                        @foreach ($bundle['rounds'] as $row)
                                            <li class="pl-5 sm:flex sm:items-start sm:justify-between sm:gap-4">
                                                <div class="min-w-0 flex-1 border-l-2 border-violet-100 py-4 pl-4">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="rounded-md bg-violet-50 px-2 py-0.5 text-xs font-bold tabular-nums text-violet-950">Ronda {{ $row->sequence }}</span>
                                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">{{ $row->persona->label() }}</span>
                                                        <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold text-indigo-950">{{ $row->status->label() }}</span>
                                                    </div>
                                                    @if ($row->chat_prep_messages)
                                                        <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Da preparação (chat)</p>
                                                        <p class="mt-0.5 line-clamp-3 whitespace-pre-wrap text-xs text-slate-600">{{ Str::limit(strip_tags($row->chat_prep_messages), 280) }}</p>
                                                    @endif
                                                    @if ($row->learnings)
                                                        <p class="{{ $row->chat_prep_messages ? 'mt-3' : 'mt-2' }} text-[11px] font-semibold uppercase tracking-wide text-slate-400">Aprendizados</p>
                                                        <p class="mt-0.5 line-clamp-3 whitespace-pre-wrap text-xs text-slate-600">{{ Str::limit(strip_tags($row->learnings), 280) }}</p>
                                                    @endif
                                                    <p class="mt-3 text-[11px] text-slate-400">Atualizado {{ $row->updated_at->diffForHumans() }}</p>
                                                </div>
                                                <div class="flex shrink-0 gap-2 border-t border-slate-50 py-4 pl-4 sm:flex-col sm:border-t-0 sm:py-4 sm:pl-2 sm:pr-4 md:flex-row md:py-8">
                                                    <a href="{{ route('agents.interview-preparations.edit', [$agent, $row]) }}"
                                                       class="inline-flex justify-center rounded-lg border border-indigo-300 bg-white px-3 py-2 text-sm font-semibold text-indigo-900 hover:bg-indigo-50">Editar</a>
                                                    <form method="post" action="{{ route('agents.interview-preparations.destroy', [$agent, $row]) }}" class="inline" onsubmit="return confirm('Remover esta ronda?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex justify-center rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Apagar</button>
                                                    </form>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-8">
                            {{ $processGroups->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
