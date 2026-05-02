<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-8">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <a href="{{ route('career-trail.index') }}" class="text-blue-600 hover:underline text-sm">&larr; Voltar à trilha</a>
                            <h1 class="text-2xl font-bold mt-2">Biblioteca de CVs e vagas</h1>
                            <p class="text-gray-600 text-sm mt-1">
                                Escolha predefinições para o ChatKit e associe um CV a cada vaga. Pode <strong>alterar</strong> títulos e textos dos CVs e JDs nas listas abaixo (formulário em cada linha) ou apagar entradas antigas.
                                No chat clássico (CV+JD), o CV predefinido é reutilizado automaticamente.
                                Cada texto na biblioteca tem no máximo {{ number_format($maxCvBodyChars, 0, ',', '.') }} caracteres (CV) e {{ number_format($maxJdBodyChars, 0, ',', '.') }} (JD), contagem Unicode — o mesmo limite evita mensagens excessivas ao enviar conteúdo pelo ChatKit.
                            </p>
                        </div>
                    </div>

                    @if (session('status'))
                        <div class="rounded-md bg-green-50 text-green-800 px-4 py-2 text-sm">{{ session('status') }}</div>
                    @endif

                    @php
                        $defJdId = $defaults?->default_jd_document_id;
                        $defaultProfileCvId = $profileCvs->firstWhere('is_default', true)?->id;
                    @endphp

                    <section class="border border-gray-200 rounded-lg p-4">
                        <h2 class="font-semibold text-lg mb-3">Predefinições</h2>
                        <p class="text-sm text-gray-600 mb-3">
                            Os CVs são sempre do seu perfil. Para o ATS, cada vaga (JD) aponta para um CV do perfil (ou fica em rascunho sem CV).
                        </p>
                        <form method="post" action="{{ route('agents.documents.defaults', $agent) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Vaga (JD) predefinida</label>
                                <select name="default_jd_document_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="">— nenhuma —</option>
                                    @foreach ($jds as $jd)
                                        <option value="{{ $jd->id }}" @selected((int) $defJdId === (int) $jd->id)>
                                            {{ $jd->title ?: 'Vaga #'.$jd->id }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-900">
                                Guardar predefinições
                            </button>
                        </form>
                    </section>

                    <section class="border border-gray-200 rounded-lg p-4">
                        <h2 class="font-semibold text-lg mb-3">CV do perfil — criar ou editar</h2>
                        <p class="text-sm text-gray-600 mb-3">Você pode ajustar um CV existente aqui (sem sair do ATS) ou criar uma nova versão.</p>

                        <div class="mb-3 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Escolher CV do perfil para editar (opcional)</label>
                            <select id="profile-cv-picker" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="">— criar novo CV —</option>
                                @foreach ($profileCvs as $pcv)
                                    <option value="{{ $pcv->id }}" @selected((int) $defaultProfileCvId === (int) $pcv->id)>
                                        {{ ($pcv->title ?: 'CV do perfil').' (#'.$pcv->id.')' }}{{ $pcv->is_default ? ' — predefinido' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Ao selecionar, o formulário abaixo será preenchido para edição. Se escolher “criar novo”, será criada uma nova versão em <code>user_cvs</code>.</p>
                        </div>

                        <form id="profile-cv-form" method="post" action="{{ route('agents.profile-cvs.store', $agent) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" id="profile-cv-method" name="_method" value="">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Título (opcional)</label>
                                <input id="cv-title" type="text" name="title" class="w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="Ex.: CV — Vaga X">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Texto do CV (máx. {{ number_format($maxCvBodyChars, 0, ',', '.') }} caracteres)</label>
                                <textarea id="cv-body" name="body" rows="8" required maxlength="{{ $maxCvBodyChars }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm font-mono"></textarea>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="make_default" value="1" class="rounded border-gray-300">
                                Definir como CV predefinido do perfil
                            </label>
                            <div>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Guardar CV</button>
                            </div>
                        </form>
                    </section>

                    <section class="border border-gray-200 rounded-lg p-4">
                        <h2 class="font-semibold text-lg mb-3">Nova vaga (JD)</h2>
                        <form method="post" action="{{ route('agents.documents.store', $agent) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="type" value="jd">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Título (opcional)</label>
                                <input type="text" name="title" class="w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="Ex.: Engenheiro de software">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CV associado a esta vaga</label>
                                <select name="user_cv_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="">— rascunho (sem CV) —</option>
                                    @foreach ($profileCvs as $pcv)
                                        <option value="{{ $pcv->id }}" @selected((int) $defaultProfileCvId === (int) $pcv->id)>
                                            {{ ($pcv->title ?: 'CV do perfil').' (#'.$pcv->id.')' }}{{ $pcv->is_default ? ' — predefinido' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Pode deixar sem CV e voltar depois. Para executar o ATS, a vaga precisa ter um CV associado.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Texto da vaga (máx. {{ number_format($maxJdBodyChars, 0, ',', '.') }} caracteres)</label>
                                <textarea name="body" rows="8" required maxlength="{{ $maxJdBodyChars }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm font-mono"></textarea>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="set_as_default" value="1" class="rounded border-gray-300">
                                Definir como vaga predefinida
                            </label>
                            <div>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Adicionar vaga</button>
                            </div>
                        </form>
                    </section>

                    <section>
                        <h2 class="font-semibold text-lg mb-3">Vagas (JD) — editar ou atualizar</h2>
                        <p class="text-sm text-gray-600 mb-3">Cada vaga guardada pode ser revista abaixo: título, texto da descrição e CV associado. Use <strong>Guardar alterações</strong> após editar.</p>
                        @if ($jds->isEmpty())
                            <p class="text-gray-500 text-sm">Ainda não tem vagas na biblioteca.</p>
                        @else
                            <ul class="divide-y divide-gray-200 border border-gray-200 rounded-lg">
                                @foreach ($jds as $jd)
                                    <li class="p-4 space-y-3">
                                        <div class="flex flex-wrap justify-between gap-2">
                                            <div>
                                                <span class="font-medium">{{ $jd->title ?: 'Vaga #'.$jd->id }}</span>
                                                @if ((int) $defJdId === (int) $jd->id)
                                                    <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">predefinida</span>
                                                @endif
                                                @if ($jd->userCv)
                                                    <p class="text-xs text-gray-500 mt-1">CV associado: {{ ($jd->userCv->title ?: 'CV do perfil #'.$jd->userCv->id).($jd->userCv->is_default ? ' — predefinido' : '') }}</p>
                                                @endif
                                            </div>
                                            <form method="post" action="{{ route('agents.documents.destroy', [$agent, $jd]) }}" onsubmit="return confirm('Remover esta vaga?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm text-red-600 hover:underline">Apagar</button>
                                            </form>
                                        </div>
                                        <form method="post" action="{{ route('agents.documents.update', [$agent, $jd]) }}" class="space-y-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="title" value="{{ old('title', $jd->title) }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="Título">
                                            <select name="user_cv_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                                <option value="">— rascunho (sem CV) —</option>
                                                @foreach ($profileCvs as $pcv)
                                                    <option value="{{ $pcv->id }}" @selected((int) $jd->user_cv_id === (int) $pcv->id)>
                                                        {{ ($pcv->title ?: 'CV do perfil').' (#'.$pcv->id.')' }}{{ $pcv->is_default ? ' — predefinido' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <textarea name="body" rows="6" required maxlength="{{ $maxJdBodyChars }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm font-mono">{{ old('body', $jd->body) }}</textarea>
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
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    (function () {
        var sel = document.getElementById('profile-cv-picker');
        var form = document.getElementById('profile-cv-form');
        var methodEl = document.getElementById('profile-cv-method');
        var titleEl = document.getElementById('cv-title');
        var bodyEl = document.getElementById('cv-body');
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

        // initial state
        if (sel.value) {
            setUpdateMode(sel.value);
            loadCv(sel.value);
        } else {
            setCreateMode();
        }
    })();
</script>
