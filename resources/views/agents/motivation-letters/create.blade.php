<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nova carta · {{ $agent->name }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 text-sm sm:px-0">
                <a href="{{ route('agents.motivation-letters.index', $agent) }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">&larr; Biblioteca de cartas</a>
                <span class="text-slate-300">·</span>
                <a href="{{ route('agents.chat', $agent) }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">Gerar no chat</a>
                <span class="text-slate-300">·</span>
                <a href="{{ $documentsHubUrl }}" class="font-medium text-emerald-700 hover:text-emerald-950 hover:underline">Biblioteca ATS</a>
            </div>

            @include('agents.motivation-letters.partials.graca-panel')

            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl">
                <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-5">
                    <h1 class="text-lg font-semibold text-slate-900">Salvar carta manualmente</h1>
                    <p class="mt-1 text-sm text-slate-600">Escolha o processo (vaga + CV de perfil na biblioteca ATS). Se já existir carta para essa vaga, o conteúdo é substituído.</p>
                </div>

                <div class="px-6 py-6">
                    @if ($jdOptions === [])
                        <p class="text-sm text-amber-900">Não há vagas com par CV associado na biblioteca. <a href="{{ $documentsHubUrl }}" class="font-medium text-indigo-600 hover:underline">Abrir biblioteca ATS</a></p>
                    @else
                        <form method="post" action="{{ route('agents.motivation-letters.store', $agent) }}" class="space-y-5">
                            @csrf
                            <div>
                                <label for="jd_document_id" class="block text-sm font-medium text-slate-700">Processo (vaga ATS)</label>
                                <select id="jd_document_id" name="jd_document_id" required
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                @error('jd_document_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="title" class="block text-sm font-medium text-slate-700">Título (opcional)</label>
                                <input id="title" type="text" name="title" value="{{ old('title') }}" maxlength="255"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                @error('title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="body" class="block text-sm font-medium text-slate-700">Texto da carta</label>
                                <textarea id="body" name="body" rows="16" required
                                          class="mt-1 block w-full rounded-lg border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('body') }}</textarea>
                                @error('body')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <input type="hidden" name="source" value="{{ \App\Models\MotivationLetter::SOURCE_MANUAL }}" />
                            <div class="flex flex-wrap gap-2">
                                <x-ui.button type="submit" variant="primary" size="md">Salvar</x-ui.button>
                                <x-ui.button variant="secondary" size="md" href="{{ route('agents.motivation-letters.index', $agent) }}">Cancelar</x-ui.button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
