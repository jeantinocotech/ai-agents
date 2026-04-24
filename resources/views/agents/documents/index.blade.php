<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-8">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <a href="{{ route('agents.show', $agent) }}" class="text-blue-600 hover:underline text-sm">&larr; Voltar ao agente</a>
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
                        $defCvId = $defaults?->default_cv_document_id;
                        $defJdId = $defaults?->default_jd_document_id;
                    @endphp

                    <section class="border border-gray-200 rounded-lg p-4">
                        <h2 class="font-semibold text-lg mb-3">Predefinições</h2>
                        <form method="post" action="{{ route('agents.documents.defaults', $agent) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CV predefinido</label>
                                <select name="default_cv_document_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="">— nenhum —</option>
                                    @foreach ($cvs as $cv)
                                        <option value="{{ $cv->id }}" @selected((int) $defCvId === (int) $cv->id)>
                                            {{ $cv->title ?: 'CV #'.$cv->id }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
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
                        <h2 class="font-semibold text-lg mb-3">Novo CV</h2>
                        <form method="post" action="{{ route('agents.documents.store', $agent) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="type" value="cv">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Título (opcional)</label>
                                <input type="text" name="title" class="w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="Ex.: CV atualizado">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Texto do CV (máx. {{ number_format($maxCvBodyChars, 0, ',', '.') }} caracteres)</label>
                                <textarea name="body" rows="8" required maxlength="{{ $maxCvBodyChars }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm font-mono"></textarea>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="set_as_default" value="1" class="rounded border-gray-300">
                                Definir como CV predefinido
                            </label>
                            <div>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Adicionar CV</button>
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
                                <select name="paired_cv_document_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="">— nenhum (só texto da vaga) —</option>
                                    @foreach ($cvs as $cv)
                                        <option value="{{ $cv->id }}" @selected((int) $defCvId === (int) $cv->id)>
                                            {{ $cv->title ?: 'CV #'.$cv->id }}
                                        </option>
                                    @endforeach
                                </select>
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
                        <h2 class="font-semibold text-lg mb-3">CVs guardados — editar ou atualizar</h2>
                        <p class="text-sm text-gray-600 mb-3">Altere o título ou o corpo e clique em <strong>Guardar alterações</strong> na linha do documento.</p>
                        @if ($cvs->isEmpty())
                            <p class="text-gray-500 text-sm">Ainda não tem CVs na biblioteca.</p>
                        @else
                            <ul class="divide-y divide-gray-200 border border-gray-200 rounded-lg">
                                @foreach ($cvs as $cv)
                                    <li class="p-4 space-y-3">
                                        <div class="flex flex-wrap justify-between gap-2">
                                            <div>
                                                <span class="font-medium">{{ $cv->title ?: 'CV #'.$cv->id }}</span>
                                                @if ((int) $defCvId === (int) $cv->id)
                                                    <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">predefinido</span>
                                                @endif
                                            </div>
                                            <form method="post" action="{{ route('agents.documents.destroy', [$agent, $cv]) }}" onsubmit="return confirm('Remover este CV?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm text-red-600 hover:underline">Apagar</button>
                                            </form>
                                        </div>
                                        <form method="post" action="{{ route('agents.documents.update', [$agent, $cv]) }}" class="space-y-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="title" value="{{ old('title', $cv->title) }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="Título">
                                            <textarea name="body" rows="6" required maxlength="{{ $maxCvBodyChars }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm font-mono">{{ old('body', $cv->body) }}</textarea>
                                            <label class="inline-flex items-center gap-2 text-sm">
                                                <input type="checkbox" name="set_as_default" value="1" class="rounded border-gray-300" @checked((int) $defCvId === (int) $cv->id)>
                                                Predefinido
                                            </label>
                                            <button type="submit" class="text-sm text-blue-600 hover:underline">Guardar alterações</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
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
                                                @if ($jd->pairedCv)
                                                    <p class="text-xs text-gray-500 mt-1">CV associado: {{ $jd->pairedCv->title ?: 'CV #'.$jd->pairedCv->id }}</p>
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
                                            <select name="paired_cv_document_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                                <option value="">— sem CV associado —</option>
                                                @foreach ($cvs as $cv)
                                                    <option value="{{ $cv->id }}" @selected((int) $jd->paired_cv_document_id === (int) $cv->id)>
                                                        {{ $cv->title ?: 'CV #'.$cv->id }}
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
