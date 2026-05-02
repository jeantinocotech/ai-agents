<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cartas de motivação · {{ $agent->name }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 text-sm sm:px-0">
                <a href="{{ route('career-trail.index') }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">&larr; Voltar à trilha</a>
                <span class="text-slate-300">·</span>
                <a href="{{ route('agents.chat', $agent) }}" class="font-medium text-violet-700 hover:text-violet-950 hover:underline">Assistência IA para nova carta (chat)</a>
                <span class="text-slate-300">·</span>
                <a href="{{ $documentsHubUrl }}" class="font-medium text-emerald-700 hover:text-emerald-950 hover:underline">Biblioteca ATS (CV e vagas)</a>
            </div>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl">
                <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/80 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h1 class="text-lg font-semibold text-slate-900">Biblioteca de cartas</h1>
                        <p class="mt-1 text-sm text-slate-600">Uma carta guardada por processo (vaga ATS + CV de perfil). Pesquise pelo título, texto ou nome da vaga.</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <a href="{{ route('agents.motivation-letters.create', $agent) }}"
                           class="inline-flex items-center justify-center rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-700">
                            Nova carta (manual)
                        </a>
                        <a href="{{ route('agents.chat', $agent) }}"
                           class="inline-flex items-center justify-center rounded-xl border border-violet-500 bg-white px-4 py-2.5 text-sm font-semibold text-violet-950 shadow-sm hover:bg-violet-50">
                            Nova carta (chat)
                        </a>
                    </div>
                </div>

                <div class="border-b border-slate-100 px-6 py-4">
                    <form method="get" action="{{ route('agents.motivation-letters.index', $agent) }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="min-w-0 flex-1">
                            <label for="ml-q" class="sr-only">Pesquisar</label>
                            <input id="ml-q" type="search" name="q" value="{{ $search }}" placeholder="Pesquisar titulo, texto ou vaga..."
                                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <button type="submit" class="inline-flex justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Pesquisar</button>
                        @if ($search !== '')
                            <a href="{{ route('agents.motivation-letters.index', $agent) }}" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Limpar</a>
                        @endif
                    </form>
                </div>

                <div class="px-6 py-6">
                    @if ($jdOptions === [])
                        <p class="text-sm text-amber-900">Ainda não há vagas (JD) na biblioteca ATS para associar. Abra a <a href="{{ $documentsHubUrl }}" class="font-medium text-indigo-600 hover:underline">biblioteca ATS</a> e associe um CV profissional a cada vaga.</p>
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
                                        <a href="{{ route('agents.motivation-letters.edit', [$agent, $letter]) }}"
                                           class="rounded-lg border border-indigo-300 bg-white px-3 py-2 text-sm font-semibold text-indigo-900 hover:bg-indigo-50">Editar</a>
                                        <form method="post" action="{{ route('agents.motivation-letters.destroy', [$agent, $letter]) }}" onsubmit="return confirm('Remover esta carta permanentemente?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Apagar</button>
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
