<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar entrevista · {{ $agent->name }}
        </h2>
    </x-slot>

    @php($jd = $prep->jdDocument)

    <div class="py-10">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 text-sm sm:px-0">
                <a href="{{ route('agents.interview-preparations.index', $agent) }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">&larr; Registros</a>
                <span class="text-slate-300">·</span>
                <a href="{{ route('agents.chat', $agent) }}" class="font-medium text-violet-700 hover:text-violet-950 hover:underline">Preparar no chat</a>
            </div>

            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl">
                <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-5">
                    <h1 class="text-lg font-semibold text-slate-900">Ronda {{ $prep->sequence }}</h1>
                    @if ($jd)
                        <p class="mt-1 text-sm text-slate-600">
                            Processo: <strong>{{ $jd->title ?: 'Vaga #'.$jd->id }}</strong>
                            @if ($jd->relationLoaded('userCv') && $jd->userCv)
                                · CV perfil {{ $jd->userCv->title ?: '#'.$jd->userCv->id }}
                            @endif
                        </p>
                    @endif
                    <p class="mt-1 text-xs text-slate-500">A sequência e o processo (vaga) não são alteráveis aqui; crie um novo registro para outra ronda ou processo.</p>
                </div>

                <div class="px-6 py-6">
                    <form method="post" action="{{ route('agents.interview-preparations.update', [$agent, $prep]) }}" class="space-y-5">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="persona" class="block text-sm font-medium text-slate-700">Persona</label>
                            <select id="persona" name="persona" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($personaOptions as $opt)
                                    <option value="{{ $opt['value'] }}" @selected(old('persona', $prep->persona->value) === $opt['value'])>{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                            @error('persona')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-slate-700">Estado</label>
                            <select id="status" name="status" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($statusOptions as $opt)
                                    <option value="{{ $opt['value'] }}" @selected(old('status', $prep->status->value) === $opt['value'])>{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="chat_prep_messages" class="block text-sm font-medium text-slate-700">Principais mensagens da preparação (chat)</label>
                            <p class="mt-1 text-xs text-slate-500">O que podes tirar das sugestões e do diálogo com o agente no chat.</p>
                            <textarea id="chat_prep_messages" name="chat_prep_messages" rows="8"
                                      class="mt-1 block w-full rounded-lg border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('chat_prep_messages', $prep->chat_prep_messages) }}</textarea>
                            @error('chat_prep_messages')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="learnings" class="block text-sm font-medium text-slate-700">Principais aprendizados</label>
                            <p class="mt-1 text-xs text-slate-500">Histórico do processo e base para futuras dicas ao candidato.</p>
                            <textarea id="learnings" name="learnings" rows="8"
                                      class="mt-1 block w-full rounded-lg border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('learnings', $prep->learnings) }}</textarea>
                            @error('learnings')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="inline-flex rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-700">Salvar</button>
                            <a href="{{ route('agents.interview-preparations.index', $agent) }}" class="inline-flex rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
