{{-- Biblioteca ATS: usada em agents/documents/index e career-trail/ats --}}
@php
    $trailReturnCareerTrailAts = $trailReturnCareerTrailAts ?? false;
    $suppressSessionFlash = $suppressSessionFlash ?? false;
    $omitHeading = $omitHeading ?? false;
@endphp

<div class="p-6 text-gray-900 space-y-8">
    @if (! $omitHeading)
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('career-trail.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Voltar à trilha</a>
                <h2 class="mt-2 text-2xl font-bold">Biblioteca de CVs e vagas</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Comece por criar ou editar uma <strong>vaga (JD)</strong> e associar um CV do perfil; depois pode definir a <strong>JD predefinida</strong> para o ChatKit e, ao final, <strong>criar ou ajustar</strong> outro CV de perfil se precisar.
                    Pode alterar títulos e textos ou apagar entradas nas secções correspondentes.
                    Cada texto na biblioteca tem no máximo {{ number_format($maxCvBodyChars, 0, ',', '.') }} caracteres (CV) e {{ number_format($maxJdBodyChars, 0, ',', '.') }} (JD), Unicode.
                </p>
            </div>
        </div>
    @endif

    @if (! $suppressSessionFlash && session('status'))
        <div class="rounded-md bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    @php
        $defJdId = $defaults?->default_jd_document_id;
        $defaultProfileCvId = $profileCvs->firstWhere('is_default', true)?->id;
    @endphp

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-lg font-semibold">Nova vaga (JD)</h3>
        <form method="post" action="{{ route('agents.documents.store', $agent) }}" class="space-y-3">
            @csrf
            @if ($trailReturnCareerTrailAts)
                <input type="hidden" name="trail_return" value="career_trail_ats">
            @endif
            <input type="hidden" name="type" value="jd">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Título (opcional)</label>
                <input type="text" name="title" class="w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="Ex.: Engenheiro de software">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">CV associado a esta vaga</label>
                <select name="user_cv_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                    <option value="">— rascunho (sem CV) —</option>
                    @foreach ($profileCvs as $pcv)
                        <option value="{{ $pcv->id }}" @selected((int) $defaultProfileCvId === (int) $pcv->id)>
                            {{ ($pcv->title ?: 'CV do perfil').' (#'.$pcv->id.')' }}{{ $pcv->is_default ? ' — predefinido' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Pode deixar sem CV e voltar depois. Para o <strong>ATS check</strong>, a vaga precisa ter um CV associado.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Texto da vaga (máx. {{ number_format($maxJdBodyChars, 0, ',', '.') }} caracteres)</label>
                <textarea name="body" rows="8" required maxlength="{{ $maxJdBodyChars }}" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm"></textarea>
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="set_as_default" value="1" class="rounded border-gray-300">
                Definir como vaga predefinida
            </label>
            <div>
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Adicionar vaga</button>
            </div>
        </form>
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-lg font-semibold">Vagas (JD) — editar ou atualizar</h3>
        <p class="mb-3 text-sm text-gray-600">Cada vaga guardada pode ser revista abaixo: título, texto da descrição e CV associado. Use <strong>Guardar alterações</strong> após editar.</p>
        @if ($jds->isEmpty())
            <p class="text-sm text-gray-500">Ainda não tem vagas na biblioteca.</p>
        @else
            <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200">
                @foreach ($jds as $jd)
                    <li class="space-y-3 p-4">
                        <div class="flex flex-wrap justify-between gap-2">
                            <div>
                                <span class="font-medium">{{ $jd->title ?: 'Vaga #'.$jd->id }}</span>
                                @if ((int) $defJdId === (int) $jd->id)
                                    <span class="ml-2 rounded bg-blue-100 px-2 py-0.5 text-xs text-blue-800">predefinida</span>
                                @endif
                                @if ($jd->userCv)
                                    <p class="mt-1 text-xs text-gray-500">CV associado: {{ ($jd->userCv->title ?: 'CV do perfil #'.$jd->userCv->id).($jd->userCv->is_default ? ' — predefinido' : '') }}</p>
                                @endif
                            </div>
                            <form method="post" action="{{ route('agents.documents.destroy', [$agent, $jd]) }}" onsubmit="return confirm('Remover esta vaga?');">
                                @csrf
                                @method('DELETE')
                                @if ($trailReturnCareerTrailAts)
                                    <input type="hidden" name="trail_return" value="career_trail_ats">
                                @endif
                                <button type="submit" class="text-sm text-red-600 hover:underline">Apagar</button>
                            </form>
                        </div>
                        <form method="post" action="{{ route('agents.documents.update', [$agent, $jd]) }}" class="space-y-2">
                            @csrf
                            @method('PUT')
                            @if ($trailReturnCareerTrailAts)
                                <input type="hidden" name="trail_return" value="career_trail_ats">
                            @endif
                            <input type="text" name="title" value="{{ old('title', $jd->title) }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="Título">
                            <select name="user_cv_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">— rascunho (sem CV) —</option>
                                @foreach ($profileCvs as $pcv)
                                    <option value="{{ $pcv->id }}" @selected((int) $jd->user_cv_id === (int) $pcv->id)>
                                        {{ ($pcv->title ?: 'CV do perfil').' (#'.$pcv->id.')' }}{{ $pcv->is_default ? ' — predefinido' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <textarea name="body" rows="6" required maxlength="{{ $maxJdBodyChars }}" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm">{{ old('body', $jd->body) }}</textarea>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="set_as_default" value="1" class="rounded border-gray-300" @checked((int) $defJdId === (int) $jd->id)>
                                Predefinida
                            </label>
                            <button type="submit" class="text-sm text-blue-600 hover:underline">Guardar alterações</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-lg font-semibold">Predefinições</h3>
        <p class="mb-3 text-sm text-gray-600">
            Os CVs são sempre do seu perfil. Para o ATS, cada vaga (JD) aponta para um CV do perfil (ou fica em rascunho sem CV). A predefinição indica qual JD o ChatKit sugere por defeito quando existem várias vagas.
        </p>
        <form method="post" action="{{ route('agents.documents.defaults', $agent) }}" class="space-y-3">
            @csrf
            @if ($trailReturnCareerTrailAts)
                <input type="hidden" name="trail_return" value="career_trail_ats">
            @endif
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Vaga (JD) predefinida</label>
                <select name="default_jd_document_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                    <option value="">— nenhuma —</option>
                    @foreach ($jds as $jd)
                        <option value="{{ $jd->id }}" @selected((int) $defJdId === (int) $jd->id)>
                            {{ $jd->title ?: 'Vaga #'.$jd->id }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-900">
                Guardar predefinições
            </button>
        </form>
    </section>

    <section class="rounded-lg border border-gray-200 p-4">
        <h3 class="mb-3 text-lg font-semibold">CV do perfil — criar ou editar</h3>
        <p class="mb-3 text-sm text-gray-600">Ajuste ou crie outra versão do CV sem sair da biblioteca — útil depois de já ter definido as vagas acima.</p>

        <div class="mb-3 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
            <label class="mb-1 block text-sm font-medium text-gray-700">Escolher CV do perfil para editar (opcional)</label>
            <select id="profile-cv-picker-{{ $agent->id }}" class="profile-cv-picker w-full rounded-md border-gray-300 text-sm shadow-sm">
                <option value="">— criar novo CV —</option>
                @foreach ($profileCvs as $pcv)
                    <option value="{{ $pcv->id }}" @selected((int) $defaultProfileCvId === (int) $pcv->id)>
                        {{ ($pcv->title ?: 'CV do perfil').' (#'.$pcv->id.')' }}{{ $pcv->is_default ? ' — predefinido' : '' }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">Ao selecionar, o formulário abaixo será preenchido para edição. Se escolher «criar novo», será criada uma nova versão em <code>user_cvs</code>.</p>
        </div>

        <form id="profile-cv-form-{{ $agent->id }}" method="post" action="{{ route('agents.profile-cvs.store', $agent) }}" class="profile-cv-form space-y-3">
            @csrf
            @if ($trailReturnCareerTrailAts)
                <input type="hidden" name="trail_return" value="career_trail_ats">
            @endif
            <input type="hidden" id="profile-cv-method-{{ $agent->id }}" name="_method" value="">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Título (opcional)</label>
                <input id="cv-title-{{ $agent->id }}" type="text" name="title" class="w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="Ex.: CV — Vaga X">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Texto do CV (máx. {{ number_format($maxCvBodyChars, 0, ',', '.') }} caracteres)</label>
                <textarea id="cv-body-{{ $agent->id }}" name="body" rows="8" required maxlength="{{ $maxCvBodyChars }}" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm"></textarea>
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="make_default" value="1" class="rounded border-gray-300">
                Definir como CV predefinido do perfil
            </label>
            <div>
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Guardar CV</button>
            </div>
        </form>
    </section>
</div>

<script>
    (function () {
        var aid = @json((int) $agent->id);
        var sel = document.getElementById('profile-cv-picker-' + aid);
        var form = document.getElementById('profile-cv-form-' + aid);
        var methodEl = document.getElementById('profile-cv-method-' + aid);
        var titleEl = document.getElementById('cv-title-' + aid);
        var bodyEl = document.getElementById('cv-body-' + aid);
        if (!sel || !form || !titleEl || !bodyEl || !methodEl) return;

        function setCreateMode() {
            form.setAttribute('action', @json(route('agents.profile-cvs.store', $agent)));
            methodEl.value = '';
        }

        function setUpdateMode(id) {
            form.setAttribute('action', @json(route('agents.profile-cvs.update', [$agent, 0])).replace('/0', '/' + id));
            methodEl.value = 'PATCH';
        }

        async function loadCv(id) {
            var res = await fetch(@json(route('agents.documents.profile-cv-content', [$agent, 0])).replace('/0/', '/' + id + '/'), {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;
            var data = await res.json();
            titleEl.value = typeof data.title === 'string' ? data.title : '';
            bodyEl.value = typeof data.body === 'string' ? data.body : '';
        }

        sel.addEventListener('change', async function () {
            var id = sel.value;
            if (!id) {
                setCreateMode();
                titleEl.value = '';
                bodyEl.value = '';
                return;
            }
            setUpdateMode(id);
            await loadCv(id);
        });

        if (sel.value) {
            setUpdateMode(sel.value);
            loadCv(sel.value);
        } else {
            setCreateMode();
        }
    })();
</script>
