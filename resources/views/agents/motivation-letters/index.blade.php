<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Passo 3 - Carta de Apresentação
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 text-sm sm:px-0">
                <a href="{{ route('career-trail.index') }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">&larr; Voltar à trilha</a>
                <span class="text-slate-300">·</span>
                <a href="{{ route('agents.chat', $agent) }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">Gerar nova carta</a>
                <span class="text-slate-300">·</span>
                <a href="{{ $documentsHubUrl }}" class="font-medium text-emerald-700 hover:text-emerald-950 hover:underline">Vagas e CVs</a>
            </div>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            @include('agents.motivation-letters.partials.graca-panel')

            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl">
                <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/80 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <x-ui.button variant="primary" size="sm" href="{{ route('agents.motivation-letters.create', $agent) }}">
                            Nova carta (manual)
                        </x-ui.button>
                        <x-ui.button variant="outline" size="sm" href="{{ route('agents.chat', $agent) }}">
                            Assistente - Criar Carta de Apresentação
                        </x-ui.button>
                    </div>
                </div>

                <div class="border-b border-slate-100 px-6 py-4">
                    <form method="get" action="{{ route('agents.motivation-letters.index', $agent) }}"
                          class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                        <input id="ml-q" type="search" name="q" value="{{ $search }}"
                               placeholder="Pesquisar titulo, texto ou vaga..."
                               aria-label="Pesquisar"
                               class="h-10 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <div class="flex flex-wrap gap-2 sm:justify-end">
                            <x-ui.button type="submit" variant="primary" size="field" class="w-full sm:w-auto">Pesquisar</x-ui.button>
                            @if ($search !== '')
                                <x-ui.button variant="secondary" size="field" class="w-full sm:w-auto" href="{{ route('agents.motivation-letters.index', $agent) }}">Limpar</x-ui.button>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="px-6 py-6">
                    @if ($jdOptions === [])
                        <p class="text-sm text-amber-900">Ainda não há vagas cadastradas. Abra a <a href="{{ $documentsHubUrl }}" class="font-medium text-indigo-600 hover:underline">biblioteca ATS</a> e associe um CV profissional a cada vaga.</p>
                    @elseif ($letters->isEmpty())
                        <p class="text-sm text-slate-600">Sem cartas guardadas com estes critérios. Utilize <strong>Nova carta</strong> ou gere texto no <a href="{{ route('agents.chat', $agent) }}" class="font-medium text-indigo-600 hover:underline">chat</a> e cole aqui ao gravar.</p>
                    @else
                        <ul class="divide-y divide-slate-200">
                            @foreach ($letters as $letter)
                                @php($jd = $letter->jdDocument)
                                <li class="flex flex-col gap-4 py-5 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-semibold text-slate-900">{{ $letter->title ?: 'Sem título' }}</span>
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">{{ $letter->source }}</span>
                                        </div>
                                        @if ($jd)
                                            <p class="mt-1 text-sm text-indigo-900">
                                                Processo: <strong>{{ $jd->title ?: 'Vaga #'.$jd->id }}</strong>
                                                @if ($jd->relationLoaded('userCv') && $jd->userCv)
                                                    · CV perfil: {{ $jd->userCv->title ?: '#'.$jd->userCv->id }}
                                                @endif
                                            </p>
                                        @endif
                                        <p class="mt-2 line-clamp-3 whitespace-pre-wrap font-mono text-xs text-slate-600">{{ Str::limit(strip_tags($letter->body), 280) }}</p>
                                        <p class="mt-2 text-xs text-slate-400">Atualizada {{ $letter->updated_at->diffForHumans() }}</p>
                                    </div>
                                    <div class="flex shrink-0 flex-wrap gap-2">
                                        <x-ui.button variant="outline" size="sm" href="{{ route('agents.motivation-letters.edit', [$agent, $letter]) }}">
                                            Editar
                                        </x-ui.button>
                                        <form method="post" action="{{ route('agents.motivation-letters.destroy', [$agent, $letter]) }}" class="inline" onsubmit="return confirm('Remover esta carta permanentemente?');">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button type="submit" variant="danger" size="sm">Apagar</x-ui.button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-6">
                            {{ $letters->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
