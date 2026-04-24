<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Trilha — Criar ou revisar o CV
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('career-trail.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                    &larr; Voltar à trilha
                </a>
                <a href="{{ route('dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900 hover:underline">
                    Dashboard
                </a>
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

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/80 px-6 py-5">
                    <h1 class="text-xl font-bold text-slate-900">O seu CV de perfil</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Fica associado à sua conta como <strong>CV padrão</strong>. Pode <strong>ajustar o texto quando quiser</strong> (ficheiro, colar ou editar na caixa) e voltar a guardar — mesmo depois de avançar na trilha.
                        Nos chats ChatKit, aparece como <strong>CV do perfil</strong>; pode também copiá-lo para a biblioteca de um agente.
                    </p>
                </div>

                <div class="px-6 py-6 space-y-8">
                    @if ($defaultCv)
                        <div class="space-y-3">
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50/50 px-4 py-3 text-sm text-emerald-900">
                                <strong>CV atual:</strong> {{ $defaultCv->title ?: 'Sem título' }}
                                · {{ number_format(mb_strlen((string) $defaultCv->body), 0, ',', '.') }} caracteres
                                · atualizado {{ $defaultCv->updated_at->diffForHumans() }}
                            </div>
                            <div class="rounded-lg border border-red-100 bg-red-50/40 px-4 py-3">
                                <p class="text-xs text-red-950/90">
                                    Remove o CV padrão da sua conta nesta página. As cópias já guardadas na <strong>biblioteca de cada agente</strong> não são apagadas.
                                </p>
                                <form method="post" action="{{ route('career-trail.cv.destroy') }}" class="mt-2"
                                      onsubmit="return confirm('Tem a certeza de que quer remover o CV de perfil? Esta ação não pode ser anulada.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-red-700 underline decoration-red-300 underline-offset-2 hover:text-red-900">
                                        Remover CV de perfil
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    @if ($cvAssistantChatUrl)
                        <div class="rounded-xl border border-indigo-200 bg-indigo-50/60 px-5 py-4">
                            @if (! $defaultCv)
                                <h2 class="text-sm font-semibold text-indigo-950">Criador de CV com assistente</h2>
                                <p class="mt-1 text-sm text-indigo-900/90">
                                    Abra o chat (ChatKit): guia-o na criação do texto. Depois copie o resultado para o campo abaixo e guarde como CV padrão do perfil.
                                </p>
                            @else
                                <h2 class="text-sm font-semibold text-indigo-950">Criador de CV — disponível sempre</h2>
                                <p class="mt-1 text-sm text-indigo-900/90">
                                    Pode reabrir o assistente a qualquer momento para rever ideias ou reescrever secções, mesmo já tendo um CV guardado ou tendo avançado na trilha. Copie o texto para o formulário abaixo e guarde para atualizar o perfil.
                                </p>
                            @endif
                            <button type="button" id="btn-open-cv-assistant"
                                    class="mt-3 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                Abrir assistente de CV
                            </button>
                        </div>
                    @elseif (! $cvAssistantChatUrl)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                            Para criar um CV com o assistente em chat, defina <code class="rounded bg-white px-1 py-0.5 font-mono text-[11px]">CAREER_TRAIL_CV_CHATKIT_AGENT_ID</code>
                            no ambiente com o ID de um agente ativo com integração ChatKit.
                        </div>
                    @endif

                    <form method="post" action="{{ route('career-trail.cv.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <fieldset>
                            <legend class="text-sm font-semibold text-slate-800">Já tem um CV pronto?</legend>
                            <p class="mt-1 text-xs text-slate-500">Se sim, pode carregar ficheiro (TXT, PDF, Word) ou colar o texto. Se não, escreva ou cole o rascunho abaixo.</p>
                            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-4 py-3 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/50">
                                    <input type="radio" name="has_existing_cv" value="1" class="text-indigo-600" @checked(old('has_existing_cv', '1') === '1')>
                                    <span class="text-sm font-medium text-slate-800">Sim, quero carregar ou colar um CV existente</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-4 py-3 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/50">
                                    <input type="radio" name="has_existing_cv" value="0" class="text-indigo-600" @checked(old('has_existing_cv') === '0')>
                                    <span class="text-sm font-medium text-slate-800">Não, vou começar pelo texto (rascunho)</span>
                                </label>
                            </div>
                        </fieldset>

                        <div>
                            <label for="cv_title" class="block text-sm font-medium text-slate-700">Título (opcional)</label>
                            <input type="text" name="title" id="cv_title" value="{{ old('title', $defaultCv?->title) }}"
                                   class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Ex.: CV — área comercial">
                        </div>

                        <div>
                            <label for="cv_file" class="block text-sm font-medium text-slate-700">Ficheiro (opcional)</label>
                            <input type="file" name="cv_file" id="cv_file" accept=".txt,.pdf,.doc,.docx"
                                   class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                            <p class="mt-1 text-xs text-slate-500">TXT, PDF ou Word. Se carregar ficheiro, o texto extraído tem prioridade sobre a caixa abaixo.</p>
                        </div>

                        <div>
                            <label for="cv_body" class="block text-sm font-medium text-slate-700">Texto do CV</label>
                            <textarea name="body" id="cv_body" rows="12" maxlength="{{ $maxCvBodyChars }}"
                                      class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Cole aqui o conteúdo completo do CV ou o rascunho que quer desenvolver.">{{ old('body', $defaultCv?->body) }}</textarea>
                            <p class="mt-1 text-xs text-slate-500">Máximo {{ number_format($maxCvBodyChars, 0, ',', '.') }} caracteres (contagem Unicode).</p>
                            <x-input-error class="mt-2" :messages="$errors->get('body')" />
                        </div>

                        <div class="rounded-lg border border-amber-100 bg-amber-50/60 px-4 py-3">
                            <label for="linkedin_url" class="block text-sm font-semibold text-amber-950">LinkedIn (perfil)</label>
                            <input type="text" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url', $linkedinUrl) }}"
                                   class="mt-1 w-full rounded-lg border-amber-200 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                   placeholder="https://www.linkedin.com/in/…">
                            <p class="mt-2 text-xs text-amber-950/90">
                                Guarde o URL público do seu perfil. A importação automática do LinkedIn depende de APIs e permissões comerciais —
                                por agora o link serve de referência e para futuras integrações; pode colar manualmente o resumo ou experiências no campo de texto.
                            </p>
                            <x-input-error class="mt-2" :messages="$errors->get('linkedin_url')" />
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                {{ $defaultCv ? 'Atualizar CV padrão do perfil' : 'Guardar como CV padrão do perfil' }}
                            </button>
                        </div>
                    </form>

                    @if ($defaultCv && $agents->isNotEmpty())
                        <div class="border-t border-slate-200 pt-6">
                            <h2 class="text-sm font-semibold text-slate-800">Copiar para a biblioteca de um agente</h2>
                            <p class="mt-1 text-xs text-slate-500">Cria uma cópia na biblioteca ChatKit desse agente e define essa cópia como CV predefinido lá.</p>
                            <ul class="mt-3 divide-y divide-slate-100 rounded-lg border border-slate-200">
                                @foreach ($agents as $ag)
                                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                                        <span class="text-sm font-medium text-slate-800">{{ $ag->name }}</span>
                                        <form method="post" action="{{ route('career-trail.cv.sync', $ag) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                                Copiar CV do perfil →
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
                    var chatUrl = @json($cvAssistantChatUrl);
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
        @endif
    </div>
</x-app-layout>
