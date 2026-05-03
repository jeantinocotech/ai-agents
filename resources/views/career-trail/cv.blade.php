<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Passo 1 — O seu CV
        </h2>
    </x-slot>

    @php
        $formCv = $editingCv ?? null;
        $profileCount = $profileCvs->count();
        $firstCvSuggestion = ! $formCv && $profileCount === 0;
        $defaultLen = $defaultCv ? mb_strlen(trim((string) $defaultCv->body)) : 0;
        $defaultMeetsTrail = $defaultLen >= (int) $minProfileCvChars;
        $cvSourceLabels = [
            'manual' => 'Escrito na conta',
            'upload' => 'Carregado',
            'agent_import' => 'Da biblioteca de um agente',
        ];
    @endphp

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('career-trail.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                    &larr; Voltar à trilha
                </a>
                <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-900">Etapa atual na trilha · Criar CV</span>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Orientação da Graça + objetivo do passo 1 (texto em BD: slot cv_page_intro) --}}
            <div class="mb-6 overflow-hidden rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white shadow-sm">
                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start">
                    <div class="shrink-0 rounded-2xl bg-white p-1 shadow ring-1 ring-violet-100">
                        <img src="{{ asset(config('career_trail.mentor_avatar', 'img/graca-avatar.png')) }}"
                             alt="{{ config('career_trail.mentor_label', 'Sra. Graça') }}"
                             class="h-16 w-16 rounded-xl object-cover" />
                    </div>
                    <div class="min-w-0 flex-1 text-sm leading-relaxed text-slate-700">
                        <p class="font-semibold text-violet-950">{{ config('career_trail.mentor_label', 'Sra. Graça') }}</p>
                        <x-graca-slot
                            :placement="\App\Support\CareerTrailGracaSlots::CV_PAGE_INTRO"
                            :step="$cvTrailStep"
                            :min-chars-placeholder="$minProfileCvChars"
                            :fallback="'Você pode salvar vários CVs na conta e escolher qual é o padrão — é esse que a trilha usa para avançar (texto com pelo menos __MIN_CHARS__ caracteres) e que os assistentes tratam como o CV do perfil na conta. CVs criados na biblioteca ATS também podem ser copiados ou geridos aqui.'"
                        />
                    </div>
                </div>
            </div>

            {{-- Passo 2: duas opções claras (antes do checklist) --}}
            <div class="mb-6">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">2 · Escolha</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Adicionar CV na conta</h3>
                        <p class="mt-2 flex-1 text-sm text-slate-600">
                            Carregue TXT, PDF ou Word, ou cole o texto no formulário — é o caminho direto para cumprir o passo&nbsp;1 da trilha.
                        </p>
                        <a href="#sec-cv-form"
                           class="mt-4 inline-flex w-fit items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                            Ir para o formulário
                        </a>
                    </div>
                    <div class="flex flex-col rounded-xl border border-indigo-200 bg-indigo-50/60 p-5 shadow-sm">
                        @if ($cvAssistantChatUrl)
                            <h3 class="text-sm font-semibold text-indigo-950">Assistente de CV</h3>
                            <p class="mt-2 flex-1 text-sm text-indigo-900/90">
                                @if ($profileCvs->isEmpty())
                                    Converse no chat e depois <strong>cole o resultado no formulário</strong> (secção abaixo) e guarde como novo CV.
                                @else
                                    Reabra para afinar secções; copie o texto para o formulário ao criar ou editar um CV.
                                @endif
                            </p>
                            <button type="button" id="btn-open-cv-assistant"
                                    class="mt-4 inline-flex w-fit items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                Abrir assistente de CV
                            </button>
                        @else
                            <h3 class="text-sm font-semibold text-slate-800">Assistente de CV</h3>
                            <p class="mt-2 text-xs text-slate-600">
                                Ainda não está disponível: na administração, associe um agente ChatKit ativo ao passo <strong>Criar um CV</strong> da trilha (campo Passo da trilha ao criar ou editar o agente).
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Passo 3: checklist / progresso na trilha --}}
            @if ($defaultCv)
                <div @class([
                    'mb-6 rounded-2xl border px-5 py-4 shadow-sm ring-1',
                    'border-emerald-300 bg-emerald-50 ring-emerald-200/60' => $defaultMeetsTrail,
                    'border-amber-300 bg-amber-50 ring-amber-200/60' => ! $defaultMeetsTrail,
                ])>
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-600/80">3 · Progresso</p>
                    <h3 @class([
                        'text-sm font-semibold',
                        'text-emerald-950' => $defaultMeetsTrail,
                        'text-amber-950' => ! $defaultMeetsTrail,
                    ])>
                        Checklist — Passo 1
                    </h3>

                    <p @class([
                        'mt-2 text-sm',
                        'text-emerald-900/90' => $defaultMeetsTrail,
                        'text-amber-950/80' => ! $defaultMeetsTrail,
                    ])>
                        O seu CV padrão é <strong>{{ $defaultCv->title ?: 'Sem título' }}</strong> e tem
                        <strong>{{ number_format($defaultLen, 0, ',', '.') }}</strong> caracteres
                        (mínimo: <strong>{{ number_format((int) $minProfileCvChars, 0, ',', '.') }}</strong>).
                    </p>

                    @if (count($cvStepChecklist ?? []) > 0)
                        <ul class="mt-3 space-y-1.5 text-sm">
                            @foreach ($cvStepChecklist as $item)
                                <li @class([
                                    'flex gap-2',
                                    'text-emerald-800' => $item['done'],
                                    'text-slate-700' => ! $item['done'],
                                ])>
                                    <span class="shrink-0 font-bold" aria-hidden="true">{{ $item['done'] ? '✓' : '○' }}</span>
                                    <span>{{ $item['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if (! ($cvStepReadiness['ready'] ?? false) && ! empty($cvStepReadiness['blocked_message']))
                        <p class="mt-3 rounded-lg bg-amber-100/60 px-3 py-2 text-xs text-amber-950">{{ $cvStepReadiness['blocked_message'] }}</p>
                    @endif

                    @if ($defaultMeetsTrail)
                        <div class="mt-4 flex flex-wrap gap-3">
                            <a href="{{ route('career-trail.index') }}"
                               class="inline-flex items-center rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">
                                Ir à trilha (ATS liberado)
                            </a>
                            <span class="self-center text-xs text-emerald-900/80">A trilha avança automaticamente para o passo ATS assim que este requisito é cumprido.</span>
                        </div>
                    @else
                        <p class="mt-3 text-xs text-amber-950/70">
                            Dica: se já tiver outro CV mais completo, marque-o como <strong>padrão</strong> na lista Meus CVs mais abaixo.
                        </p>
                    @endif
                </div>
            @elseif (count($cvStepChecklist ?? []) > 0)
                <div class="mb-6 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">3 · Progresso</p>
                    <h3 class="text-sm font-semibold text-slate-900">Checklist — Passo 1</h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($cvStepChecklist as $item)
                            <li @class([
                                'flex gap-2',
                                'text-emerald-800' => $item['done'],
                                'text-slate-600' => ! $item['done'],
                            ])>
                                <span class="shrink-0 font-bold" aria-hidden="true">{{ $item['done'] ? '✓' : '○' }}</span>
                                <span>{{ $item['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if (! ($cvStepReadiness['ready'] ?? false) && ! empty($cvStepReadiness['blocked_message']))
                        <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-950">{{ $cvStepReadiness['blocked_message'] }}</p>
                    @endif
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-5">
                    <h1 class="text-xl font-bold text-slate-900">Meus CVs</h1>
                </div>

                <div class="px-6 py-6 space-y-8">
                    @if ($profileCvs->isNotEmpty())
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Meus CVs</h2>
                            <ul class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
                                @foreach ($profileCvs as $cv)
                                    <li class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-medium text-slate-900">{{ $cv->title ?: 'Sem título' }}</span>
                                                @if ($cv->is_default)
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-900">Padrão</span>
                                                @endif
                                                <span class="text-xs text-slate-500">{{ $cvSourceLabels[(string) $cv->source] ?? $cv->source }}</span>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-600">
                                                {{ number_format(mb_strlen((string) $cv->body), 0, ',', '.') }} caracteres
                                                · atualizado {{ $cv->updated_at->diffForHumans() }}
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                                            <a href="{{ route('career-trail.cv', ['edit' => $cv->id]) }}"
                                               class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-50">
                                                Editar
                                            </a>
                                            @if (! $cv->is_default)
                                                <form method="post" action="{{ route('career-trail.cv.default', $cv) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-900 hover:bg-indigo-100">
                                                        Usar como padrão
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="post" action="{{ route('career-trail.cv.destroy', $cv) }}" class="inline"
                                                  onsubmit="return confirm('Eliminar este CV da conta? Esta ação não pode ser anulada.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($agentLibraryCvs->isNotEmpty())
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">CVs nas bibliotecas dos agentes (ATS / ChatKit)</h2>
                            <p class="mt-1 text-xs text-slate-600">São os documentos que criou por etapa; pode copiar para a conta, abrir na biblioteca do agente ou remover (vagas emparelhadas podem perder o CV associado).</p>
                            <ul class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-slate-50/40">
                                @foreach ($agentLibraryCvs as $doc)
                                    <li class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="font-medium text-slate-900">{{ $doc->title ?: ('CV #'.$doc->id) }}</p>
                                            <p class="mt-0.5 text-xs text-slate-600">
                                                Agente: <strong>{{ $doc->agent->name ?? '—' }}</strong>
                                                · {{ number_format(mb_strlen((string) $doc->body), 0, ',', '.') }} caracteres
                                                · {{ $doc->updated_at->diffForHumans() }}
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap gap-2 shrink-0">
                                            <a href="{{ $doc->agent ? \App\Services\CareerTrailAgentAccess::documentsHubUrl($doc->agent) : route('agents.documents.index', $doc->agent_id) }}"
                                               class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-50">
                                                Abrir biblioteca
                                            </a>
                                            <form method="post" action="{{ route('career-trail.cv.import-agent') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="agent_document_id" value="{{ $doc->id }}">
                                                @if ($profileCount === 0)
                                                    <input type="hidden" name="make_default" value="1">
                                                @endif
                                                <button type="submit" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-900 hover:bg-indigo-100">
                                                    Copiar para a conta
                                                </button>
                                            </form>
                                            @if ($profileCount > 0)
                                                <form method="post" action="{{ route('career-trail.cv.import-agent') }}" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="agent_document_id" value="{{ $doc->id }}">
                                                    <input type="hidden" name="make_default" value="1">
                                                    <button type="submit" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-900 hover:bg-emerald-100">
                                                        Copiar e tornar padrão
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="post" action="{{ route('career-trail.cv.agent-document.destroy', [$doc->agent_id, $doc->id]) }}" class="inline"
                                                  onsubmit="return confirm('Remover este CV da biblioteca deste agente?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">
                                                    Eliminar no agente
                                                </button>
                                            </form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div id="sec-cv-form" class="scroll-mt-24">
                    @if ($formCv)
                        <form method="post" action="{{ route('career-trail.cv.update', $formCv) }}" class="space-y-6 rounded-xl border border-indigo-200 bg-indigo-50/30 px-4 py-5 sm:px-5">
                            @csrf
                            @method('PATCH')
                    @else
                        <form method="post" action="{{ route('career-trail.cv.store') }}" class="space-y-6 rounded-xl border border-transparent px-1">
                            @csrf
                    @endif

                        @if ($formCv)
                            <div class="flex justify-end">
                                <a href="{{ route('career-trail.cv') }}" class="text-xs font-semibold text-indigo-700 hover:underline">Cancelar edição</a>
                            </div>
                        @endif

                        @if ($firstCvSuggestion)
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-950">
                                É o seu <strong>primeiro CV</strong> na conta — ao salvar, será definido automaticamente como <strong>padrão</strong> (você pode alterar depois na lista de CVs).
                            </div>
                        @endif

                        @if (! $firstCvSuggestion && ! $formCv)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-4 py-3">
                                <input type="checkbox" name="make_default" value="1" class="mt-1 rounded border-slate-300 text-indigo-600" @checked(old('make_default'))>
                                <span class="text-sm text-slate-700"><strong>Definir como CV padrão</strong> da conta ao salvar (substitui o padrão atual).</span>
                            </label>
                        @endif

                        @if ($formCv)
                            @if ($formCv->is_default)
                                <p class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-950">
                                    Este CV é o <strong>padrão</strong> na conta. Para usar outro como principal, escolha Usar como padrão na lista ou marque a opção em outro CV ao salvar.
                                </p>
                            @else
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-4 py-3">
                                    <input type="checkbox" name="make_default" value="1" class="mt-1 rounded border-slate-300 text-indigo-600" @checked(old('make_default'))>
                                    <span class="text-sm text-slate-700"><strong>Tornar este o CV padrão</strong> ao salvar.</span>
                                </label>
                            @endif
                        @endif

                        <div>
                            <label for="cv_title" class="block text-sm font-medium text-slate-700">Título</label>
                            <input type="text" name="title" id="cv_title" value="{{ old('title', $formCv?->title) }}"
                                   class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Ex.: CV — área comercial"
                                   required autocomplete="off">
                            <x-input-error class="mt-2" :messages="$errors->get('title')" />
                        </div>

                        <div>
                            <span class="block text-sm font-medium text-slate-700">Tenho um CV <span class="font-normal text-slate-500"></span></span>
                            <div class="mt-1 flex flex-wrap items-center gap-3">
                                <label class="inline-flex cursor-pointer items-center rounded-lg bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 shadow-sm ring-1 ring-inset ring-indigo-100 hover:bg-indigo-100 focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-indigo-500">
                                    <span>Selecionar arquivo</span>
                                    <input type="file" id="cv_extract_file" accept=".txt,.pdf,.doc,.docx" class="sr-only">
                                </label>
                                <span id="cv_extract_filename"
                                      class="min-w-0 max-w-full flex-1 truncate text-sm text-slate-600"
                                      aria-live="polite"
                                      title="">Nenhum arquivo selecionado</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Opcional — Aceita TXT, PDF ou Word (até 20&nbsp;MB). Ao escolher o arquivo, o texto extraído é colocado na área Texto do CV para você revisar antes de salvar.</p>
                            <p id="cv_extract_status" class="mt-2 hidden text-xs font-medium text-slate-700" role="status" aria-live="polite"></p>
                            <x-input-error class="mt-2" :messages="$errors->get('cv_file')" />
                        </div>

                        <div>
                            <label for="cv_body" class="block text-sm font-medium text-slate-700">Conteúdo do CV</label>
                            <textarea name="body" id="cv_body" rows="14" maxlength="{{ $maxCvBodyChars }}"
                                      class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Cole ou escreva o conteúdo completo do CV.">{{ old('body', $formCv?->body) }}</textarea>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <p class="text-slate-500">Limite máximo {{ number_format($maxCvBodyChars, 0, ',', '.') }} caracteres (Unicode).</p>
                                <p id="cv-char-hint" class="font-medium text-slate-600" aria-live="polite">
                                    <span id="cv-char-count">0</span> caracteres
                                    · mínimo trilha (CV padrão): <span class="text-indigo-700">{{ number_format($minProfileCvChars, 0, ',', '.') }}</span>
                                </p>
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('body')" />
                        </div>

                        <details class="rounded-lg border border-slate-200 bg-slate-50/80 px-4 py-3">
                            <summary class="cursor-pointer text-sm font-semibold text-slate-800">LinkedIn (opcional)</summary>
                            <p class="mt-2 text-xs text-slate-600">
                                Guarde o URL público do perfil como referência; pode colar experiências manualmente no texto do CV.
                            </p>
                            <label for="linkedin_url" class="mt-3 block text-sm font-medium text-slate-700">URL do perfil</label>
                            <input type="text" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url', $linkedinUrl) }}"
                                   class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="https://www.linkedin.com/in/…">
                            <x-input-error class="mt-2" :messages="$errors->get('linkedin_url')" />
                        </details>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                {{ $formCv ? 'Salvar alterações' : 'Salvar CV' }}
                            </button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>

        @if ($cvAssistantChatUrl)
            <div id="cv-assistant-modal" class="fixed inset-0 z-[60] hidden" role="dialog" aria-modal="true" aria-labelledby="cv-assistant-modal-title">
                <button type="button" class="absolute inset-0 h-full w-full cursor-default border-0 bg-slate-900/50 p-0" data-cv-assistant-backdrop aria-label="Fechar"></button>
                <div class="pointer-events-none absolute inset-3 flex flex-col sm:inset-6 md:inset-10">
                    <div class="pointer-events-auto flex max-h-full min-h-0 flex-1 flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10">
                        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <h2 id="cv-assistant-modal-title" class="text-sm font-semibold text-slate-900">Assistente de CV</h2>
                            <button type="button" data-cv-assistant-close
                                    class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200">
                                Fechar
                            </button>
                        </div>
                        <iframe id="cv-assistant-iframe" title="Assistente de CV" class="min-h-0 w-full flex-1 border-0 bg-white"
                                loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                </div>
            </div>
        @endif
        <script>
            (function () {
                var ta = document.getElementById('cv_body');
                var countEl = document.getElementById('cv-char-count');
                var hintEl = document.getElementById('cv-char-hint');
                var minTrail = {{ (int) $minProfileCvChars }};
                var fileEl = document.getElementById('cv_extract_file');
                var filenameEl = document.getElementById('cv_extract_filename');
                var titleEl = document.getElementById('cv_title');
                var statusEl = document.getElementById('cv_extract_status');
                var extractUrl = @json(route('career-trail.cv.extract-file'));
                var tokenEl = document.querySelector('meta[name="csrf-token"]');

                function updateCount() {
                    if (!ta || !countEl) return;
                    var n = Array.from(ta.value).length;
                    countEl.textContent = n;
                    if (hintEl) {
                        hintEl.classList.toggle('text-emerald-700', n >= minTrail);
                        hintEl.classList.toggle('text-amber-800', n > 0 && n < minTrail);
                        hintEl.classList.toggle('text-slate-600', n === 0);
                    }
                }

                if (ta) {
                    ta.addEventListener('input', updateCount);
                    updateCount();
                }

                function syncCvExtractFilename() {
                    if (!filenameEl || !fileEl) return;
                    if (!fileEl.files || !fileEl.files[0]) {
                        filenameEl.textContent = 'Nenhum arquivo selecionado';
                        filenameEl.removeAttribute('title');
                        return;
                    }
                    var n = fileEl.files[0].name;
                    filenameEl.textContent = n;
                    filenameEl.setAttribute('title', n);
                }

                if (fileEl && statusEl && extractUrl && tokenEl) {
                    fileEl.addEventListener('change', function () {
                        syncCvExtractFilename();
                        if (!fileEl.files || !fileEl.files[0]) return;

                        var fd = new FormData();
                        fd.append('cv_file', fileEl.files[0]);
                        fd.append('_token', tokenEl.getAttribute('content'));

                        statusEl.classList.remove('hidden', 'text-red-700', 'text-emerald-800');
                        statusEl.classList.add('text-slate-700');
                        statusEl.textContent = 'Extraindo texto do arquivo…';

                        fetch(extractUrl, {
                            method: 'POST',
                            body: fd,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': tokenEl.getAttribute('content')
                            },
                            credentials: 'same-origin'
                        })
                            .then(function (r) {
                                return r.json().then(function (j) {
                                    return { ok: r.ok, json: j };
                                });
                            })
                            .then(function (res) {
                                if (!res.ok) {
                                    var msg = res.json.message
                                        || (res.json.errors && res.json.errors.cv_file && res.json.errors.cv_file[0])
                                        || 'Não foi possível extrair texto deste arquivo.';
                                    statusEl.textContent = msg;
                                    statusEl.classList.remove('hidden', 'text-slate-700', 'text-emerald-800');
                                    statusEl.classList.add('text-red-700');
                                    return;
                                }
                                if (ta) {
                                    ta.value = res.json.body || '';
                                    updateCount();
                                    ta.focus();
                                }
                                if (titleEl && res.json.suggested_title) {
                                    if ((titleEl.value || '').trim() === '') {
                                        titleEl.value = res.json.suggested_title;
                                    }
                                }
                                statusEl.textContent = 'Texto colado em Texto do CV. Revise e clique em Salvar quando estiver pronto.';
                                statusEl.classList.remove('hidden', 'text-slate-700', 'text-red-700');
                                statusEl.classList.add('text-emerald-800');
                                fileEl.value = '';
                                syncCvExtractFilename();
                            })
                            .catch(function () {
                                statusEl.classList.remove('hidden', 'text-slate-700', 'text-emerald-800');
                                statusEl.classList.add('text-red-700');
                                statusEl.textContent = 'Erro ao enviar o arquivo. Tente novamente.';
                            });
                    });
                }
            })();
        </script>
        @if ($cvAssistantChatUrl)
            <script>
                (function () {
                    var modal = document.getElementById('cv-assistant-modal');
                    var iframe = document.getElementById('cv-assistant-iframe');
                    var assistantBtn = document.getElementById('btn-open-cv-assistant');
                    var chatUrl = @json($cvAssistantChatIframeUrl ?? '');
                    if (!modal || !iframe || !assistantBtn || !chatUrl) return;

                    function openModal() {
                        if (!iframe.getAttribute('src')) {
                            iframe.setAttribute('src', chatUrl);
                        }
                        modal.classList.remove('hidden');
                        document.body.classList.add('overflow-hidden');
                    }
                    function closeModal() {
                        modal.classList.add('hidden');
                        document.body.classList.remove('overflow-hidden');
                    }

                    assistantBtn.addEventListener('click', openModal);
                    modal.querySelectorAll('[data-cv-assistant-close], [data-cv-assistant-backdrop]').forEach(function (el) {
                        el.addEventListener('click', closeModal);
                    });
                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
                    });
                })();
            </script>
        @endif
    </div>
</x-app-layout>
