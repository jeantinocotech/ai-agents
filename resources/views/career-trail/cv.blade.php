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

            {{-- Orientação da Graça + objetivo do passo 1 --}}
            <div class="mb-6 overflow-hidden rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white shadow-sm">
                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start">
                    <div class="shrink-0 rounded-2xl bg-white p-1 shadow ring-1 ring-violet-100">
                        <img src="{{ asset(config('career_trail.mentor_avatar', 'img/graca-avatar.png')) }}"
                             alt="{{ config('career_trail.mentor_label', 'Sra. Graça') }}"
                             class="h-16 w-16 rounded-xl object-cover" />
                    </div>
                    <div class="min-w-0 flex-1 text-sm leading-relaxed text-slate-700">
                        <p class="font-semibold text-violet-950">{{ config('career_trail.mentor_label', 'Sra. Graça') }}</p>
                        <p class="mt-2">
                            Pode guardar <strong class="text-slate-900">vários CVs na conta</strong> e escolher qual é o <strong class="text-slate-900">predefinido</strong>
                            — é esse que a trilha usa para avançar (texto com pelo menos <strong class="text-slate-900">{{ number_format($minProfileCvChars, 0, ',', '.') }} caracteres</strong>)
                            e que os assistentes tratam como «CV do perfil». CVs criados na biblioteca ATS também pode <strong class="text-slate-900">copiar ou gerir aqui</strong>.
                        </p>
                    </div>
                </div>
            </div>

            @if ($defaultCv)
                <div @class([
                    'mb-6 rounded-2xl border px-5 py-4 shadow-sm ring-1',
                    'border-emerald-300 bg-emerald-50 ring-emerald-200/60' => $defaultMeetsTrail,
                    'border-amber-300 bg-amber-50 ring-amber-200/60' => ! $defaultMeetsTrail,
                ])>
                    <p @class([
                        'text-sm font-semibold',
                        'text-emerald-950' => $defaultMeetsTrail,
                        'text-amber-950' => ! $defaultMeetsTrail,
                    ])>
                        Passo 1 e desbloqueio do ATS
                    </p>

                    <p @class([
                        'mt-1 text-sm',
                        'text-emerald-900/90' => $defaultMeetsTrail,
                        'text-amber-950/80' => ! $defaultMeetsTrail,
                    ])>
                        O seu CV predefinido é <strong>{{ $defaultCv->title ?: 'Sem título' }}</strong> e tem
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
                            Dica: se já tiver outro CV mais completo, marque-o como <strong>predefinido</strong> na lista «Meus CVs» abaixo.
                        </p>
                    @endif
                </div>
            @elseif (count($cvStepChecklist ?? []) > 0)
                <div class="mb-6 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
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
                    <h1 class="text-xl font-bold text-slate-900">Os seus CVs na conta</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Guarde tantas versões quantas precisar; <strong class="text-slate-900">uma</strong> fica como <strong class="text-slate-900">predefinida</strong>
                        para a trilha, tokens e cópias para agentes. Cópias na biblioteca de cada agente (ex.: ATS) aparecem abaixo para copiar ou eliminar.
                    </p>
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
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-900">Predefinido</span>
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
                                                        Usar como predefinido
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
                                            <a href="{{ route('agents.documents.index', $doc->agent_id) }}"
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
                                                        Copiar e tornar predefinido
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

                    @if ($cvAssistantChatUrl)
                        <div class="rounded-xl border border-indigo-200 bg-indigo-50/60 px-5 py-4">
                            @if ($profileCvs->isEmpty())
                                <h2 class="text-sm font-semibold text-indigo-950">Opção A — Criar ou rever com o assistente</h2>
                                <p class="mt-1 text-sm text-indigo-900/90">
                                    Abra o chat, converse com o assistente e depois <strong>cole o resultado no formulário abaixo</strong> e guarde como novo CV na conta.
                                </p>
                            @else
                                <h2 class="text-sm font-semibold text-indigo-950">Assistente de CV</h2>
                                <p class="mt-1 text-sm text-indigo-900/90">
                                    Reabra quando quiser para afinar secções. Copie o texto para o formulário abaixo e guarde (novo CV ou ao editar um existente).
                                </p>
                            @endif
                            <button type="button" id="btn-open-cv-assistant"
                                    class="mt-3 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                Abrir assistente de CV
                            </button>
                        </div>
                    @else
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                            Associe na administração um agente ChatKit activo ao passo <strong>Criar um CV</strong> da trilha (campo «Passo da trilha» ao criar ou editar o agente).
                        </div>
                    @endif

                    <div class="relative">
                        <div class="absolute -top-3 left-0 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            {{ $formCv ? 'Editar CV' : 'Adicionar CV' }}
                        </div>
                    </div>

                    @if ($formCv)
                        <form method="post" action="{{ route('career-trail.cv.update', $formCv) }}" enctype="multipart/form-data" class="space-y-6 rounded-xl border border-indigo-200 bg-indigo-50/30 px-4 py-5 sm:px-5">
                            @csrf
                            @method('PATCH')
                    @else
                        <form method="post" action="{{ route('career-trail.cv.store') }}" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                    @endif

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $formCv ? 'Editar «'.($formCv->title ?: 'CV').'»' : 'Novo CV na conta' }}
                            </p>
                            @if ($formCv)
                                <a href="{{ route('career-trail.cv') }}" class="text-xs font-semibold text-indigo-700 hover:underline">Cancelar edição</a>
                            @endif
                        </div>

                        <fieldset>
                            <legend class="text-sm font-semibold text-slate-800">Já tem um CV em ficheiro?</legend>
                            <p class="mt-1 text-xs text-slate-500">Pode carregar TXT, PDF ou Word, ou colar diretamente na caixa de texto.</p>
                            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-4 py-3 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/50">
                                    <input type="radio" name="has_existing_cv" value="1" class="text-indigo-600" @checked(old('has_existing_cv', $formCv ? '1' : '1') === '1')>
                                    <span class="text-sm font-medium text-slate-800">Sim — carregar ou colar um CV existente</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-4 py-3 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/50">
                                    <input type="radio" name="has_existing_cv" value="0" class="text-indigo-600" @checked(old('has_existing_cv', $formCv ? '1' : '1') === '0')>
                                    <span class="text-sm font-medium text-slate-800">Não — escrever um rascunho só na caixa</span>
                                </label>
                            </div>
                        </fieldset>

                        @if ($firstCvSuggestion)
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-950">
                                É o seu <strong>primeiro CV</strong> na conta — ao guardar, será definido automaticamente como <strong>predefinido</strong> (pode alterar depois na lista acima).
                            </div>
                        @endif

                        @if (! $firstCvSuggestion && ! $formCv)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-4 py-3">
                                <input type="checkbox" name="make_default" value="1" class="mt-1 rounded border-slate-300 text-indigo-600" @checked(old('make_default'))>
                                <span class="text-sm text-slate-700"><strong>Definir como CV predefinido</strong> da conta ao guardar (substitui o predefinido atual).</span>
                            </label>
                        @endif

                        @if ($formCv)
                            @if ($formCv->is_default)
                                <p class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-950">
                                    Este CV é o <strong>predefinido</strong> na conta. Para usar outro como principal, escolha «Usar como predefinido» na lista ou marque a opção noutro CV ao gravar.
                                </p>
                            @else
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-4 py-3">
                                    <input type="checkbox" name="make_default" value="1" class="mt-1 rounded border-slate-300 text-indigo-600" @checked(old('make_default'))>
                                    <span class="text-sm text-slate-700"><strong>Tornar este o CV predefinido</strong> ao gravar.</span>
                                </label>
                            @endif
                        @endif

                        <div>
                            <label for="cv_title" class="block text-sm font-medium text-slate-700">Título (opcional)</label>
                            <input type="text" name="title" id="cv_title" value="{{ old('title', $formCv?->title) }}"
                                   class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Ex.: CV — área comercial">
                        </div>

                        <div>
                            <label for="cv_file" class="block text-sm font-medium text-slate-700">Ficheiro (opcional)</label>
                            <input type="file" name="cv_file" id="cv_file" accept=".txt,.pdf,.doc,.docx"
                                   class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                            <p class="mt-1 text-xs text-slate-500">Se enviar ficheiro, o texto extraído substitui o da caixa abaixo ao guardar.</p>
                        </div>

                        <div>
                            <label for="cv_body" class="block text-sm font-medium text-slate-700">Texto do CV</label>
                            <textarea name="body" id="cv_body" rows="14" maxlength="{{ $maxCvBodyChars }}"
                                      class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Cole ou escreva o conteúdo completo do CV.">{{ old('body', $formCv?->body) }}</textarea>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <p class="text-slate-500">Limite máximo {{ number_format($maxCvBodyChars, 0, ',', '.') }} caracteres (Unicode).</p>
                                <p id="cv-char-hint" class="font-medium text-slate-600" aria-live="polite">
                                    <span id="cv-char-count">0</span> caracteres
                                    · mínimo trilha (predefinido): <span class="text-indigo-700">{{ number_format($minProfileCvChars, 0, ',', '.') }}</span>
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
                                {{ $formCv ? 'Guardar alterações' : 'Guardar novo CV' }}
                            </button>
                        </div>
                    </form>

                    @if ($defaultCv && $agents->isNotEmpty())
                        <div class="border-t border-slate-200 pt-6">
                            <h2 class="text-sm font-semibold text-slate-800">Copiar CV predefinido para um agente</h2>
                            <p class="mt-1 text-xs text-slate-500">Usa o texto do CV marcado como predefinido na lista acima.</p>
                            <ul class="mt-3 divide-y divide-slate-100 rounded-lg border border-slate-200">
                                @foreach ($agents as $ag)
                                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                                        <span class="text-sm font-medium text-slate-800">{{ $ag->name }}</span>
                                        <form method="post" action="{{ route('career-trail.cv.sync', $ag) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                                Copiar predefinido →
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
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
            <script>
                (function () {
                    var modal = document.getElementById('cv-assistant-modal');
                    var iframe = document.getElementById('cv-assistant-iframe');
                    var btn = document.getElementById('btn-open-cv-assistant');
                    var ta = document.getElementById('cv_body');
                    var countEl = document.getElementById('cv-char-count');
                    var hintEl = document.getElementById('cv-char-hint');
                    var chatUrl = @json($cvAssistantChatIframeUrl ?? '');
                    var minTrail = {{ (int) $minProfileCvChars }};

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

                    if (!modal || !iframe || !btn || !chatUrl) return;

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

                    btn.addEventListener('click', openModal);
                    modal.querySelectorAll('[data-cv-assistant-close], [data-cv-assistant-backdrop]').forEach(function (el) {
                        el.addEventListener('click', closeModal);
                    });
                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                            closeModal();
                        }
                    });
                })();
            </script>
        @else
            <script>
                (function () {
                    var ta = document.getElementById('cv_body');
                    var countEl = document.getElementById('cv-char-count');
                    var hintEl = document.getElementById('cv-char-hint');
                    var minTrail = {{ (int) $minProfileCvChars }};
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
                })();
            </script>
        @endif
    </div>
</x-app-layout>
