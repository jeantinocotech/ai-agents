{{-- Biblioteca ATS: usada em agents/documents/index e career-trail/ats --}}
@php
    $trailReturnCareerTrailAts = $trailReturnCareerTrailAts ?? false;
    $suppressSessionFlash = $suppressSessionFlash ?? false;
    $omitHeading = $omitHeading ?? false;
    $defaultProfileCvId = $profileCvs->firstWhere('is_default', true)?->id;
    $editingJd = $editingJd ?? null;
    $atsAnalyzeChatUrl = $atsAnalyzeChatUrl ?? null;
    $atsChatHeading = $atsChatHeading ?? config('career_trail.ats_chat_heading', 'Passar no filtro');
    $interviewPrepAgent = $interviewPrepAgent ?? null;
    $canAccessInterviewPrep = ($canAccessInterviewPrep ?? false) === true;
    $jdListTotalCount = $jdListTotalCount ?? null;
    $jdListVisibleCount = $jdListVisibleCount ?? null;
    $showFinalizedJds = ($showFinalizedJds ?? false) === true;
@endphp

<div class="p-6 text-gray-900 space-y-8">
    @if (! $omitHeading)
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('career-trail.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Voltar à trilha</a>
                <h2 class="mt-2 text-2xl font-bold">Biblioteca de CVs e vagas</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Comece por criar ou editar uma <strong>vaga (JD)</strong> e associar um CV do perfil; o assistente usa por defeito a vaga que guardou mais recentemente (salvo quando abre o fluxo com CV e vaga já escolhidos). Depois pode <strong>criar ou ajustar</strong> outro CV de perfil se precisar.
                    Você pode alterar títulos e textos ou apagar entradas nas seções correspondentes.
                    Cada texto na biblioteca tem no máximo {{ number_format($maxCvBodyChars, 0, ',', '.') }} caracteres (CV) e {{ number_format($maxJdBodyChars, 0, ',', '.') }} (JD), Unicode.
                </p>
            </div>
        </div>
    @endif

    @if (! $suppressSessionFlash && session('status'))
        <div class="rounded-md bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    @if (! $trailReturnCareerTrailAts)
        <section class="rounded-lg border border-gray-200 p-4">
            <h3 class="mb-3 text-lg font-semibold">Nova vaga (JD)</h3>
            <form method="post" action="{{ route('agents.documents.store', $agent) }}" class="space-y-3">
                @csrf
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
                                {{ ($pcv->title ?: 'CV do perfil').' (#'.$pcv->id.')' }}{{ $pcv->is_default ? ' — padrão' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Pode deixar sem CV e voltar depois. Para <strong>{{ $atsChatHeading }}</strong>, a vaga precisa ter um CV associado.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Texto da vaga (máx. {{ number_format($maxJdBodyChars, 0, ',', '.') }} caracteres)</label>
                    <textarea name="body" rows="8" required maxlength="{{ $maxJdBodyChars }}" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm"></textarea>
                </div>
                <div>
                    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Adicionar vaga</button>
                </div>
            </form>
        </section>
    @endif

    @if ($trailReturnCareerTrailAts)
        <section class="rounded-lg border border-gray-200 p-4">
            <h3 class="mb-3 text-lg font-semibold">As suas vagas</h3>
            <p class="mb-3 text-sm text-gray-600">Clique no nome para carregar a vaga no formulário abaixo. Depois de guardar com um CV associado, use <strong>{{ $atsChatHeading }}</strong> no assistente (alinhamento ATS). Indique depois o <strong>envio do CV à empresa</strong> e, quando houver retorno, registe entrevistas no passo correspondente da trilha. O <strong>estado</strong> sincroniza-se com o resultado das entrevistas (aprovado / não prosseguiu).</p>
            @if ($jdListTotalCount !== null && $jdListVisibleCount !== null)
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-xs text-slate-700 sm:text-sm">
                    <span class="font-medium text-slate-800">Lista: <span class="tabular-nums text-slate-600">{{ $jdListVisibleCount }}</span> de <span class="tabular-nums">{{ $jdListTotalCount }}</span> vaga(s){{ $showFinalizedJds ? ' (inclui finalizadas)' : ' (só em curso)' }}.</span>
                    <div class="flex flex-wrap gap-2">
                        @if (! $showFinalizedJds && (int) $jdListVisibleCount < (int) $jdListTotalCount)
                            <a href="{{ route('career-trail.ats', array_filter(['show_finalized_jds' => 1, 'edit_jd' => request()->query('edit_jd')])) }}"
                               class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-800 hover:bg-slate-100">
                                Mostrar finalizadas
                            </a>
                        @elseif ($showFinalizedJds)
                            <a href="{{ route('career-trail.ats', array_filter(['edit_jd' => request()->query('edit_jd')])) }}"
                               class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-800 hover:bg-slate-100">
                                Só em curso
                            </a>
                        @endif
                    </div>
                </div>
            @endif
            @if ($jds->isEmpty())
                <p class="text-sm text-gray-500">Ainda não tem vagas. Use o formulário abaixo para criar a primeira.</p>
            @else
                <div class="max-h-56 overflow-x-auto overflow-y-auto rounded-xl border border-gray-200 bg-white">
                    <table class="min-w-full text-left text-sm">
                        <thead class="sticky top-0 z-10 border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-3 py-2">Vaga</th>
                                <th class="px-3 py-2">CV</th>
                                <th class="px-3 py-2">Estado</th>
                                <th class="whitespace-nowrap px-3 py-2">Atualizado</th>
                                <th class="whitespace-nowrap px-3 py-2 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($jds as $jd)
                                <tr @class([
                                    'bg-violet-50/80' => $editingJd && (int) $editingJd->id === (int) $jd->id,
                                ])>
                                    <td class="align-top px-3 py-2">
                                        <a href="{{ route('career-trail.ats', ['edit_jd' => $jd->id]) }}#sec-ats-jd-form"
                                           class="group block min-w-0 text-gray-900 hover:text-indigo-700">
                                            <span class="font-medium group-hover:underline">{{ $jd->title ?: 'Vaga #'.$jd->id }}</span>
                                        </a>
                                    </td>
                                    <td class="px-3 py-2 align-middle text-gray-600">
                                        @if ($jd->userCv)
                                            {{ ($jd->userCv->title ?: 'CV #'.$jd->userCv->id).($jd->userCv->is_default ? ' — padrão' : '') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        @php
                                            $appSt = $jd->application_status;
                                            $badgeClass = match ($appSt) {
                                                \App\Enums\JobApplicationStatus::Draft => 'bg-slate-100 text-slate-800',
                                                \App\Enums\JobApplicationStatus::Submitted => 'bg-sky-100 text-sky-900',
                                                \App\Enums\JobApplicationStatus::CvSent => 'bg-amber-100 text-amber-950',
                                                \App\Enums\JobApplicationStatus::Interviewing => 'bg-violet-100 text-violet-900',
                                                \App\Enums\JobApplicationStatus::DidNotProceed => 'bg-rose-100 text-rose-900',
                                                \App\Enums\JobApplicationStatus::Accepted => 'bg-emerald-100 text-emerald-900',
                                                default => 'bg-gray-100 text-gray-700',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $badgeClass }}">{{ $appSt?->label() ?? 'Em preparação' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2 align-middle text-gray-600">
                                        {{ $jd->updated_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-3 py-2 align-middle text-right">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            @php
                                                $canMarkNoAdvance = $appSt === null
                                                    || $appSt === \App\Enums\JobApplicationStatus::Draft
                                                    || $appSt === \App\Enums\JobApplicationStatus::Submitted
                                                    || $appSt === \App\Enums\JobApplicationStatus::CvSent;
                                            @endphp
                                            @if ($appSt === \App\Enums\JobApplicationStatus::Submitted)
                                                <form method="post" action="{{ route('agents.documents.mark-cv-sent-to-employer', [$agent, $jd]) }}" class="inline" onsubmit="return confirm('Confirmar que enviou o CV à empresa ou recrutador e está a aguardar retorno?');">
                                                    @csrf
                                                    <input type="hidden" name="trail_return" value="career_trail_ats">
                                                    <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-950 hover:bg-amber-100">
                                                        CV enviado à empresa
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($appSt === \App\Enums\JobApplicationStatus::CvSent && $interviewPrepAgent)
                                                @if ($canAccessInterviewPrep)
                                                    <a href="{{ route('agents.interview-preparations.create', $interviewPrepAgent).'?jd_document_id='.$jd->id }}"
                                                       class="inline-flex items-center rounded-lg border border-violet-300 bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-900 hover:bg-violet-100">
                                                        Registar entrevista
                                                    </a>
                                                @else
                                                    <span class="inline-flex cursor-not-allowed items-center rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-400"
                                                          title="Avance na trilha até ao passo Entrevistas para registar a primeira ronda.">
                                                        Registar entrevista
                                                    </span>
                                                @endif
                                            @endif
                                            @if ($canMarkNoAdvance)
                                                <form method="post" action="{{ route('agents.documents.mark-application-not-proceeded', [$agent, $jd]) }}" class="inline" onsubmit="return confirm('Marcar esta candidatura como «não prosseguiu»? Só disponível enquanto não existem rondas de entrevista registadas.');">
                                                    @csrf
                                                    <input type="hidden" name="trail_return" value="career_trail_ats">
                                                    <button type="submit" class="rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-800 hover:bg-rose-50">
                                                        Não prosseguiu
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="post" action="{{ route('agents.documents.destroy', [$agent, $jd]) }}" class="inline" onsubmit="return confirm('Remover esta vaga?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="trail_return" value="career_trail_ats">
                                                <button type="submit" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section id="sec-ats-jd-form" class="scroll-mt-28 rounded-lg border border-gray-200 p-4 @if($editingJd) border-indigo-200 bg-indigo-50/30 @endif">
            @if ($editingJd)
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-lg font-semibold text-slate-900">Editar vaga</h3>
                    <a href="{{ route('career-trail.ats') }}#sec-ats-jd-form"
                       class="text-sm font-semibold text-indigo-700 hover:underline">
                        Nova vaga (JD)
                    </a>
                </div>
                <form method="post" action="{{ route('agents.documents.update', [$agent, $editingJd]) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="trail_return" value="career_trail_ats">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Título (opcional)</label>
                        <input type="text" name="title" value="{{ old('title', $editingJd->title) }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="Ex.: Engenheiro de software">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">CV associado a esta vaga</label>
                        <select name="user_cv_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">— rascunho (sem CV) —</option>
                            @foreach ($profileCvs as $pcv)
                                <option value="{{ $pcv->id }}" @selected((int) $editingJd->user_cv_id === (int) $pcv->id)>
                                    {{ ($pcv->title ?: 'CV do perfil').' (#'.$pcv->id.')' }}{{ $pcv->is_default ? ' — padrão' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Para <strong>{{ $atsChatHeading }}</strong>, a vaga precisa ter um CV associado.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Texto da vaga (máx. {{ number_format($maxJdBodyChars, 0, ',', '.') }} caracteres)</label>
                        <textarea name="body" rows="8" required maxlength="{{ $maxJdBodyChars }}" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm">{{ old('body', $editingJd->body) }}</textarea>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200/80 pt-3">
                        <button type="submit" class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                            Salvar alterações
                        </button>
                        @if (! empty($atsAnalyzeChatUrl))
                            <a href="{{ $atsAnalyzeChatUrl }}"
                               class="inline-flex items-center rounded-xl border border-indigo-300 bg-white px-5 py-2.5 text-sm font-semibold text-indigo-800 shadow-sm hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2">
                                {{ $atsChatHeading }}
                            </a>
                        @else
                            <span class="inline-flex cursor-not-allowed items-center rounded-xl border border-gray-200 bg-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-400"
                                  role="note"
                                  title="Associe um CV do perfil à vaga, guarde, e confirme que o assistente ATS (ChatKit) está configurado na trilha.">
                                {{ $atsChatHeading }}
                            </span>
                        @endif
                    </div>
                </form>
            @else
                <h3 class="mb-3 text-lg font-semibold">Nova vaga (JD)</h3>
                <p class="mb-3 text-sm text-gray-600">Preencha para adicionar uma nova vaga, ou escolha uma vaga na lista acima para editar.</p>
                <form method="post" action="{{ route('agents.documents.store', $agent) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="trail_return" value="career_trail_ats">
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
                                    {{ ($pcv->title ?: 'CV do perfil').' (#'.$pcv->id.')' }}{{ $pcv->is_default ? ' — padrão' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Pode deixar sem CV e voltar depois. Para <strong>{{ $atsChatHeading }}</strong>, a vaga precisa ter um CV associado.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Texto da vaga (máx. {{ number_format($maxJdBodyChars, 0, ',', '.') }} caracteres)</label>
                        <textarea name="body" rows="8" required maxlength="{{ $maxJdBodyChars }}" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm"></textarea>
                    </div>
                    <div>
                        <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Adicionar vaga</button>
                    </div>
                </form>
            @endif
        </section>
    @endif

    @if (! $trailReturnCareerTrailAts)
        <section class="rounded-lg border border-gray-200 p-4">
            <h3 class="mb-3 text-lg font-semibold">Vagas (JD) — editar ou atualizar</h3>
            <p class="mb-3 text-sm text-gray-600">Cada vaga salva pode ser revisada abaixo: título, texto da descrição e CV associado. Use <strong>Salvar alterações</strong> após editar.</p>
            @if ($jds->isEmpty())
                <p class="text-sm text-gray-500">Ainda não tem vagas na biblioteca.</p>
            @else
                <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200">
                    @foreach ($jds as $jd)
                        <li class="space-y-3 p-4">
                            <div class="flex flex-wrap justify-between gap-2">
                                <div>
                                    <span class="font-medium">{{ $jd->title ?: 'Vaga #'.$jd->id }}</span>
                                    @if ($jd->userCv)
                                        <p class="mt-1 text-xs text-gray-500">CV associado: {{ ($jd->userCv->title ?: 'CV do perfil #'.$jd->userCv->id).($jd->userCv->is_default ? ' — padrão' : '') }}</p>
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
                                <input type="text" name="title" value="{{ old('title', $jd->title) }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="Título">
                                <select name="user_cv_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    <option value="">— rascunho (sem CV) —</option>
                                    @foreach ($profileCvs as $pcv)
                                        <option value="{{ $pcv->id }}" @selected((int) $jd->user_cv_id === (int) $pcv->id)>
                                            {{ ($pcv->title ?: 'CV do perfil').' (#'.$pcv->id.')' }}{{ $pcv->is_default ? ' — padrão' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <textarea name="body" rows="6" required maxlength="{{ $maxJdBodyChars }}" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm">{{ old('body', $jd->body) }}</textarea>
                                <button type="submit" class="text-sm text-blue-600 hover:underline">Salvar alterações</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    @if (! $trailReturnCareerTrailAts)
        <section class="rounded-lg border border-gray-200 p-4">
            <h3 class="mb-3 text-lg font-semibold">CV do perfil — criar ou editar</h3>
            <p class="mb-3 text-sm text-gray-600">Ajuste ou crie outra versão do CV sem sair da biblioteca — útil depois de já ter definido as vagas acima.</p>

            <div class="mb-3 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                <label class="mb-1 block text-sm font-medium text-gray-700">Escolher CV do perfil para editar (opcional)</label>
                <select id="profile-cv-picker-{{ $agent->id }}" class="profile-cv-picker w-full rounded-md border-gray-300 text-sm shadow-sm">
                    <option value="">— criar novo CV —</option>
                    @foreach ($profileCvs as $pcv)
                        <option value="{{ $pcv->id }}" @selected((int) $defaultProfileCvId === (int) $pcv->id)>
                            {{ ($pcv->title ?: 'CV do perfil').' (#'.$pcv->id.')' }}{{ $pcv->is_default ? ' — padrão' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Ao selecionar, o formulário abaixo será preenchido para edição. Se escolher criar novo CV, será criada uma nova versão em <code>user_cvs</code>.</p>
            </div>

            <form id="profile-cv-form-{{ $agent->id }}" method="post" action="{{ route('agents.profile-cvs.store', $agent) }}" class="profile-cv-form space-y-3">
                @csrf
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
                    Definir como CV padrão do perfil
                </label>
                <div>
                    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Salvar CV</button>
                </div>
            </form>
        </section>
    @endif
</div>

@if (! $trailReturnCareerTrailAts)
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
@endif
