<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Passo 1 — {{ $cvTrailStep?->title ?? 'Curriculum' }}
        </h2>
    </x-slot>

    @php
        $formCv = $editingCv ?? null;
        $profileCount = $profileCvs->count();
        $firstCvSuggestion = ! $formCv && $profileCount === 0;
        $canAnalyzeCvNow = $formCv !== null && ! empty($cvAnalyzeChatUrl);
    @endphp

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

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
            <x-graca-orientation-panel :page-key="\App\Support\GracaPanelPreferences::PAGE_TRAIL_CV">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div class="shrink-0 rounded-2xl bg-white p-1 shadow ring-1 ring-violet-100">
                        <img src="{{ asset(config('career_trail.mentor_avatar', 'img/graca-avatar.png')) }}"
                             alt="{{ config('career_trail.mentor_label', 'Sra. Graça') }}"
                             class="h-16 w-16 rounded-xl object-cover" />
                    </div>
                    <div class="min-w-0 flex-1 text-sm leading-relaxed text-slate-700">
                        <x-graca-slot
                            :placement="\App\Support\CareerTrailGracaSlots::CV_PAGE_INTRO"
                            :step="$cvTrailStep"
                            :min-chars-placeholder="$minProfileCvChars"
                            :fallback="'Você pode salvar vários CVs na conta e escolher qual é o padrão — é esse que a trilha usa para avançar (texto com pelo menos __MIN_CHARS__ caracteres) e que os assistentes tratam como o CV do perfil na conta. CVs criados na biblioteca ATS também podem ser copiados ou geridos aqui.'"
                        />
                    </div>
                </div>
            </x-graca-orientation-panel>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3 sm:px-5">
                    <h1 class="text-xl font-bold text-slate-900">Meus CVs</h1>
                </div>

                <div class="space-y-3 px-4 py-3 sm:px-5 sm:py-4">
                    @if ($profileCvs->isNotEmpty())
                        <div class="overflow-x-auto overflow-y-auto rounded-xl border border-slate-200 bg-white" style="max-height: 10.5rem;">
                            <table class="min-w-full text-left text-sm">
                                <thead class="sticky top-0 z-10 border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-3 py-1.5">CV</th>
                                        <th class="whitespace-nowrap px-3 py-1.5">Atualizado</th>
                                        <th class="whitespace-nowrap px-3 py-1.5 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($profileCvs as $cv)
                                        <tr @class([
                                            'bg-violet-50/80' => $formCv && (int) $formCv->id === (int) $cv->id,
                                        ])>
                                            <td class="align-top px-3 py-1.5">
                                                <a href="{{ route('career-trail.cv', ['edit' => $cv->id]) }}#sec-cv-form"
                                                   class="group block min-w-0 text-slate-900 hover:text-indigo-700">
                                                    <span class="font-medium group-hover:underline">{{ $cv->title ?: 'Sem título' }}</span>
                                                    @if ($cv->is_default)
                                                        <span class="ml-1.5 align-middle rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-900">Padrão</span>
                                                    @endif
                                                </a>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-1.5 align-middle text-slate-600">
                                                {{ $cv->updated_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-3 py-1.5 align-middle text-right">
                                                <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                                    <form method="post" action="{{ route('career-trail.cv.duplicate', $cv) }}" class="inline">
                                                        @csrf
                                                        <x-ui.button type="submit" variant="secondary" size="xs">Duplicar</x-ui.button>
                                                    </form>
                                                    <form method="post" action="{{ route('career-trail.cv.destroy', $cv) }}" class="inline"
                                                          onsubmit="return confirm('Eliminar este CV da conta? Esta ação não pode ser anulada.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-ui.button type="submit" variant="danger" size="xs">Eliminar</x-ui.button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-slate-600">Ainda não tem CVs na conta. Use o formulário abaixo ou faça upload.</p>
                    @endif


                    <div id="sec-cv-form" class="scroll-mt-24">
                    @if ($formCv)
                        <form id="cv-profile-form" method="post" action="{{ route('career-trail.cv.update', $formCv) }}" class="space-y-3 rounded-xl border border-indigo-200 bg-indigo-50/30 px-3 py-3 sm:px-4">
                            @csrf
                            @method('PATCH')
                    @else
                        <form id="cv-profile-form" method="post" action="{{ route('career-trail.cv.store') }}" class="space-y-3">
                            @csrf
                    @endif

                        <div class="flex flex-wrap items-end gap-3">
                            <div class="min-w-[12rem] flex-1">
                                <label for="cv_title" class="block text-sm font-medium text-slate-800">Título</label>
                                <input type="text" name="title" id="cv_title" value="{{ old('title', $formCv?->title) }}"
                                       class="mt-0.5 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="Ex.: CV — área comercial"
                                       required autocomplete="off">
                                <x-input-error class="mt-1" :messages="$errors->get('title')" />
                            </div>
                            <div class="flex flex-wrap items-center gap-2 pb-0.5">
                                <label class="{{ \App\Support\UiButton::classes('outline', 'sm') }} cursor-pointer">
                                    <span>Upload CV</span>
                                    <input type="file" id="cv_extract_file" accept=".txt,.pdf,.doc,.docx" class="sr-only" aria-label="Ficheiro para extrair texto">
                                </label>
                            </div>
                        </div>
                        <p id="cv_extract_filename" class="-mt-1 truncate text-xs text-slate-500" aria-live="polite">TXT, PDF ou Word (máx. 20&nbsp;MB) — o texto vai para a caixa abaixo.</p>
                        <p id="cv_extract_status" class="hidden text-xs font-medium text-slate-700" role="status" aria-live="polite"></p>
                        <x-input-error :messages="$errors->get('cv_file')" />

                        <div>
                            <label for="cv_body" class="block text-sm font-medium text-slate-800">Conteúdo do CV</label>
                            <textarea name="body" id="cv_body" rows="14" data-max-cv-body-chars="{{ $maxCvBodyChars }}"
                                      class="mt-0.5 w-full rounded-lg border-slate-300 font-mono text-sm leading-relaxed shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Cole ou escreva o CV completo.">{{ old('body', $formCv?->body) }}</textarea>
                            <div class="mt-1 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                                <span>Máx. {{ number_format($maxCvBodyChars, 0, ',', '.') }} caracteres ao salvar</span>
                                <span id="cv-char-hint" class="font-medium tabular-nums text-slate-600" aria-live="polite"><span id="cv-char-count">0</span> caracteres</span>
                            </div>
                            <x-input-error class="mt-1" :messages="$errors->get('body')" />
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <x-cv-export-dropdown form-id="cv-profile-form" :user-cv="$formCv" />
                            <x-ui.button type="submit" variant="primary" size="md">
                                {{ $formCv ? 'Salvar alterações' : 'Salvar CV' }}
                            </x-ui.button>
                            @if (! empty($cvAnalyzeChatUrl))
                                @if ($canAnalyzeCvNow)
                                    <x-ui.button id="btn-analisar-cv" variant="outline" size="md" href="{{ $cvAnalyzeChatUrl }}"
                                                 @class(['ring-2 ring-indigo-400 ring-offset-2' => session('show_analisar')])>
                                        Analisar CV
                                    </x-ui.button>
                                @else
                                    <span class="{{ \App\Support\UiButton::classes('secondary', 'md') }} cursor-not-allowed opacity-40"
                                          role="note" title="Grave o CV primeiro.">
                                        Analisar CV
                                    </span>
                                @endif
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-700">
                            @if ($firstCvSuggestion)
                                <span class="text-xs text-slate-500">O primeiro CV gravado torna-se o padrão.</span>
                            @elseif (! $formCv)
                                <label class="inline-flex cursor-pointer items-center gap-2">
                                    <input type="checkbox" name="make_default" value="1" class="rounded border-slate-300 text-indigo-600" @checked(old('make_default'))>
                                    <span>Definir como CV padrão</span>
                                </label>
                            @elseif (! $formCv->is_default)
                                <label class="inline-flex cursor-pointer items-center gap-2">
                                    <input type="checkbox" name="make_default" value="1" class="rounded border-slate-300 text-indigo-600" @checked(old('make_default'))>
                                    <span>Definir como CV padrão</span>
                                </label>
                            @else
                                <span class="text-xs font-medium text-emerald-800">CV padrão da conta</span>
                            @endif
                        </div>

                        <details class="rounded-lg border border-slate-200/80 bg-slate-50/60 px-3 py-2">
                            <summary class="cursor-pointer text-sm font-medium text-slate-800">LinkedIn</summary>
                            <label for="linkedin_url" class="mt-2 block text-xs font-medium text-slate-600">URL do perfil</label>
                            <input type="text" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url', $linkedinUrl) }}"
                                   class="mt-0.5 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="https://www.linkedin.com/in/…">
                            <x-input-error class="mt-1" :messages="$errors->get('linkedin_url')" />
                        </details>

                        @if ($formCv)
                            <details class="rounded-lg border border-slate-200/80 bg-white px-3 py-2">
                                <summary class="cursor-pointer text-sm font-medium text-slate-800">
                                    Vagas associadas
                                    @if (($editingCvAssociatedJds ?? collect())->isNotEmpty())
                                        <span class="font-normal text-slate-500">({{ $editingCvAssociatedJds->count() }})</span>
                                    @endif
                                </summary>
                                @if (($editingCvAssociatedJds ?? collect())->isNotEmpty())
                                    <ul class="mt-2 divide-y divide-slate-100 text-sm">
                                        @foreach ($editingCvAssociatedJds as $jd)
                                            <li class="flex flex-wrap items-center justify-between gap-2 py-1.5 first:pt-0 last:pb-0">
                                                <div class="min-w-0">
                                                    <span class="font-medium text-slate-800">{{ $jd->title ?: ('Vaga #'.$jd->id) }}</span>
                                                    <span class="block text-xs text-slate-500">{{ $jd->agent->name ?? '—' }}</span>
                                                </div>
                                                @if ($jd->agent)
                                                    <a href="{{ \App\Services\CareerTrailAgentAccess::documentsHubUrl($jd->agent) }}"
                                                       class="shrink-0 text-xs font-semibold text-indigo-700 hover:underline">ATS</a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="mt-2 text-xs text-slate-500">Nenhuma vaga (JD) associada.</p>
                                @endif
                            </details>
                        @endif
                    </form>
                    </div>
                </div>
            </div>
        </div>

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
                var maxCvBodyChars = {{ (int) $maxCvBodyChars }};

                function cvBodyCharCount(s) {
                    try {
                        return Array.from(s).length;
                    } catch (_) {
                        return String(s).length;
                    }
                }

                function updateCount() {
                    if (!ta || !countEl) return;
                    var n = cvBodyCharCount(ta.value);
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
                    var profileForm = ta.form;
                    if (profileForm && maxCvBodyChars > 0) {
                        profileForm.addEventListener('submit', function (ev) {
                            var n = cvBodyCharCount(ta.value);
                            if (n <= maxCvBodyChars) {
                                return;
                            }
                            ev.preventDefault();
                            window.alert('O texto do CV tem ' + n.toLocaleString('pt-BR') +
                                ' caracteres. O máximo permitido ao salvar é ' +
                                maxCvBodyChars.toLocaleString('pt-BR') +
                                '. Encurte o texto antes de continuar (ou peça para aumentar AGENT_DOCUMENT_MAX_CV_CHARS).');
                        });
                    }
                }

                var cvExtractFilenameHint = filenameEl ? filenameEl.textContent : '';

                function syncCvExtractFilename() {
                    if (!filenameEl || !fileEl) return;
                    if (!fileEl.files || !fileEl.files[0]) {
                        filenameEl.textContent = cvExtractFilenameHint;
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
                                var overExtractLimit = false;
                                if (ta) {
                                    ta.value = res.json.body || '';
                                    updateCount();
                                    ta.focus();
                                    overExtractLimit = maxCvBodyChars > 0 && cvBodyCharCount(ta.value) > maxCvBodyChars;
                                }
                                if (titleEl && res.json.suggested_title) {
                                    if ((titleEl.value || '').trim() === '') {
                                        titleEl.value = res.json.suggested_title;
                                    }
                                }
                                if (overExtractLimit) {
                                    statusEl.textContent = 'Atenção: o texto extraído tem ' +
                                        cvBodyCharCount(ta.value).toLocaleString('pt-BR') +
                                        ' caracteres; o limite ao salvar é ' +
                                        maxCvBodyChars.toLocaleString('pt-BR') +
                                        '. Enxugue o conteúdo na caixa de texto antes de salvar.';
                                    statusEl.classList.remove('hidden', 'text-slate-700', 'text-emerald-800', 'text-red-700');
                                    statusEl.classList.add('text-amber-900');
                                } else {
                                    statusEl.textContent = 'Texto colado em Texto do CV. Revise e clique em Salvar quando estiver pronto.';
                                    statusEl.classList.remove('hidden', 'text-slate-700', 'text-red-700', 'text-amber-900');
                                    statusEl.classList.add('text-emerald-800');
                                }
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
    </div>
</x-app-layout>
