{{-- Biblioteca ATS: usada em agents/documents/index e career-trail/ats --}}
@php
    use App\Enums\JobApplicationStatus;
    use App\Support\AgentsDocumentTrailListFilter;

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

    $jdListFilter = $jdListFilter ?? AgentsDocumentTrailListFilter::OPEN;
    $trailFilterQuery = static function (array $extra = []) use ($jdListFilter) {
        $q = $extra;
        if ($jdListFilter !== AgentsDocumentTrailListFilter::OPEN) {
            $q['jd_list_filter'] = $jdListFilter;
        }

        return array_filter($q, fn ($v) => $v !== null && $v !== '');
    };

    $jdArchiveInterviewConfirm = 'Esta candidatura está em processo de entrevista: ao arquivar, as rondas registadas serão removidas e o processo será actualizado como «não prosseguiu». Deseja continuar?';
    $jdDnpConfirm = 'Marcar esta candidatura como «não prosseguiu»? Só faz sentido enquanto não existem rondas de entrevista ou pode ser escolha final depois dessa fase, conforme o estado actual.';
@endphp

<div class="p-6 text-gray-900 space-y-8">
    @if (! $omitHeading)
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('career-trail.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Voltar à trilha</a>
                <h2 class="mt-2 text-2xl font-bold">Biblioteca de CVs e vagas</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Comece por criar ou editar uma <strong>vaga (JD)</strong> e associar um CV do perfil; o assistente usa por defeito a vaga que guardou mais recentemente (salvo quando abre o fluxo com CV e vaga já escolhidos). Depois pode <strong>criar ou ajustar</strong> outro CV de perfil se precisar.
                    Cada texto na biblioteca tem no máximo {{ number_format($maxCvBodyChars, 0, ',', '.') }} caracteres (CV) e {{ number_format($maxJdBodyChars, 0, ',', '.') }} (JD), Unicode.
                    Na trilha ATS, utilize o combo <strong>Estado</strong> na lista para alinhamento ATS, CV à empresa, arquivar, etc.; o filtro em cima mostra por defeito vagas activas «em curso» (sem resultado final nem arquivadas).
                </p>
            </div>
        </div>
    @endif

    @if (! $suppressSessionFlash && session('status'))
        <div class="rounded-md bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    @if (! $trailReturnCareerTrailAts)
        <form method="get" action="{{ route('agents.documents.index', $agent) }}" class="flex max-w-xl flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2.5">
                <label for="jd_list_filter_hub-{{ $agent->id }}" class="text-sm font-medium text-slate-800">Lista de vagas</label>
                <select id="jd_list_filter_hub-{{ $agent->id }}" name="jd_list_filter" onchange="this.form.submit()" class="min-w-[14rem] flex-1 rounded-md border-slate-300 text-sm shadow-sm">
                    <option value="{{ AgentsDocumentTrailListFilter::OPEN }}" @selected($jdListFilter === AgentsDocumentTrailListFilter::OPEN)>Em curso (activas não terminadas)</option>
                    <option value="{{ AgentsDocumentTrailListFilter::ACTIVE_ALL }}" @selected($jdListFilter === AgentsDocumentTrailListFilter::ACTIVE_ALL)>Todas as vagas activas</option>
                    <option value="{{ AgentsDocumentTrailListFilter::CLOSED }}" @selected($jdListFilter === AgentsDocumentTrailListFilter::CLOSED)>Encerradas (aceite ou não prosseguiu)</option>
                    <option value="{{ AgentsDocumentTrailListFilter::INACTIVE }}" @selected($jdListFilter === AgentsDocumentTrailListFilter::INACTIVE)>Inactivas / arquivadas</option>
                </select>
        </form>
    @endif

    @if ($trailReturnCareerTrailAts)
        <form method="get" action="{{ route('career-trail.ats') }}" class="flex max-w-2xl flex-wrap items-center">
            <select id="jd_list_filter_trail" name="jd_list_filter" onchange="this.form.submit()" class="min-w-[16rem] flex-1 rounded-xl border-indigo-200 text-sm shadow-sm">
                <option value="{{ AgentsDocumentTrailListFilter::OPEN }}" @selected($jdListFilter === AgentsDocumentTrailListFilter::OPEN)>Em curso (exclui encerradas e inactivas)</option>
                <option value="{{ AgentsDocumentTrailListFilter::ACTIVE_ALL }}" @selected($jdListFilter === AgentsDocumentTrailListFilter::ACTIVE_ALL)>Todas as vagas activas</option>
                <option value="{{ AgentsDocumentTrailListFilter::CLOSED }}" @selected($jdListFilter === AgentsDocumentTrailListFilter::CLOSED)>Encerradas (aceite ou não prosseguiu)</option>
                <option value="{{ AgentsDocumentTrailListFilter::INACTIVE }}" @selected($jdListFilter === AgentsDocumentTrailListFilter::INACTIVE)>Inactivas / arquivadas</option>
            </select>
        </form>
    @endif

    @if (! $trailReturnCareerTrailAts && $jdListFilter !== AgentsDocumentTrailListFilter::INACTIVE)
        <section class="rounded-lg border border-gray-200 p-4">
            <h3 class="mb-3 text-lg font-semibold">Nova vaga (JD)</h3>
            <form method="post" action="{{ route('agents.documents.store', $agent) }}" class="space-y-3">
                @csrf
                @if ($jdListFilter !== AgentsDocumentTrailListFilter::OPEN)
                    <input type="hidden" name="jd_list_filter" value="{{ $jdListFilter }}">
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

            @if ($jds->isEmpty())
                <p class="text-sm text-gray-500">
                    @if ($jdListFilter === AgentsDocumentTrailListFilter::INACTIVE)
                        Não tem vagas arquivadas neste agente ATS.
                    @elseif ($jdListFilter === AgentsDocumentTrailListFilter::CLOSED)
                        Não há vagas activas com resultado final aqui — altere o filtro ou feche mais candidaturas.
                    @else
                        Sem vagas para este critério. Se acabou de criar vagas terminadas/arquivadas, escolha outro item no filtro «Mostrar na lista». Para a primeira vaga «em curso», use o formulário abaixo.
                    @endif
                </p>
            @else
                <div class="max-h-56 overflow-x-auto overflow-y-auto rounded-xl border border-gray-200 bg-white">
                    <table class="min-w-full text-left text-sm">
                        <thead class="sticky top-0 z-10 border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-3 py-2">Vaga</th>
                                <th class="px-3 py-2">CV</th>
                                <th class="min-w-[12rem] px-3 py-2">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($jds as $jd)
                                @php
                                    $statusKey = AgentsDocumentTrailListFilter::listStatusKey($jd->is_active, $jd->application_status);
                                    $prepCountTrail = (int) ($jd->interview_preparations_count ?? 0);
                                    $jdNeedsInterviewArchiveWarnTrail =
                                        $prepCountTrail > 0
                                        || $jd->application_status === JobApplicationStatus::Interviewing
                                        || (bool) ($jd->interview_processes_exists ?? false);
                                @endphp
                                <tr @class([
                                    'bg-violet-50/80' => $editingJd && (int) $editingJd->id === (int) $jd->id,
                                ])>
                                    <td class="align-top px-3 py-2">
                                        <a href="{{ route('career-trail.ats', $trailFilterQuery(['edit_jd' => $jd->id])) }}#sec-ats-jd-form"
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
                                        <form method="post" action="{{ route('agents.documents.trail-desired-status', [$agent, $jd]) }}" class="max-w-[20rem]" id="trail-jd-status-form-{{ $jd->id }}-trail">
                                            @csrf
                                            <input type="hidden" name="trail_return" value="career_trail_ats">
                                            @if ($jdListFilter !== AgentsDocumentTrailListFilter::OPEN)
                                                <input type="hidden" name="jd_list_filter" value="{{ $jdListFilter }}">
                                            @endif
                                            @if ($interviewPrepAgent)
                                                <input type="hidden" name="interview_prep_agent_id" value="{{ $interviewPrepAgent->id }}">
                                            @endif
                                            <select name="desired_status" data-prev="{{ $statusKey }}" class="w-full rounded-md border-gray-300 text-xs shadow-sm" aria-label="Estado da vaga {{ $jd->title ?: '#'.$jd->id }}"
                                                    onchange="window.trailJdStatusPick(this, {{ json_encode([
                                                        'needsInterviewArchiveWarn' => $jdNeedsInterviewArchiveWarnTrail,
                                                        'archiveInterview' => $jdArchiveInterviewConfirm,
                                                        'dnp' => $jdDnpConfirm,
                                                        'submitted' => 'Confirmar registo do alinhamento ATS (CV+vaga enviados ao assistente)?',
                                                        'cvSent' => 'Confirmar que enviou o CV à empresa ou recrutador e está a aguardar retorno?',
                                                        'accepted' => 'Marcar esta candidatura como «Aceite»? Só faz sentido com acordo já alcançado no processo.',
                                                    ]) }});">
                                                <option value="{{ JobApplicationStatus::Draft->value }}" @selected($statusKey === JobApplicationStatus::Draft->value)>{{ JobApplicationStatus::Draft->label() }}</option>
                                                <option value="{{ JobApplicationStatus::Submitted->value }}" @selected($statusKey === JobApplicationStatus::Submitted->value)>{{ JobApplicationStatus::Submitted->label() }}</option>
                                                <option value="{{ JobApplicationStatus::CvSent->value }}" @selected($statusKey === JobApplicationStatus::CvSent->value)>{{ JobApplicationStatus::CvSent->label() }}</option>
                                                <option value="{{ JobApplicationStatus::Interviewing->value }}" @disabled(! $interviewPrepAgent || ! $canAccessInterviewPrep) @selected($statusKey === JobApplicationStatus::Interviewing->value)>{{ JobApplicationStatus::Interviewing->label() }}</option>
                                                <option value="{{ JobApplicationStatus::Accepted->value }}" @selected($statusKey === JobApplicationStatus::Accepted->value)>{{ JobApplicationStatus::Accepted->label() }}</option>
                                                <option value="{{ JobApplicationStatus::DidNotProceed->value }}" @selected($statusKey === JobApplicationStatus::DidNotProceed->value)>{{ JobApplicationStatus::DidNotProceed->label() }}</option>
                                                <option value="{{ AgentsDocumentTrailListFilter::INACTIVE }}" @selected($statusKey === AgentsDocumentTrailListFilter::INACTIVE)>Inactiva / arquivada</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        <script>
            window.trailJdStatusPick = function (sel, messages) {
                var v = sel.value;
                var prev = sel.getAttribute('data-prev');
                if (v === prev) return;
                if (v === 'inactive') {
                    if (messages.needsInterviewArchiveWarn && !window.confirm(messages.archiveInterview)) {
                        sel.value = prev;
                        return;
                    }
                    sel.form.submit();
                    return;
                }
                if (v === '{{ JobApplicationStatus::DidNotProceed->value }}') {
                    if (!window.confirm(messages.dnp)) { sel.value = prev; return; }
                }
                if (v === '{{ JobApplicationStatus::Submitted->value }}') {
                    if (!window.confirm(messages.submitted)) { sel.value = prev; return; }
                }
                if (v === '{{ JobApplicationStatus::CvSent->value }}') {
                    if (!window.confirm(messages.cvSent)) { sel.value = prev; return; }
                }
                if (v === '{{ JobApplicationStatus::Accepted->value }}') {
                    if (!window.confirm(messages.accepted)) { sel.value = prev; return; }
                }
                sel.form.submit();
            };
        </script>

        <section id="sec-ats-jd-form" class="scroll-mt-28 rounded-lg border border-gray-200 p-4 @if($editingJd) border-indigo-200 bg-indigo-50/30 @endif">
            @if ($editingJd)
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-lg font-semibold text-slate-900">Editar vaga</h3>
                    <a href="{{ route('career-trail.ats', $trailFilterQuery([])) }}#sec-ats-jd-form"
                       class="text-sm font-semibold text-indigo-700 hover:underline">
                        Nova vaga
                    </a>
                </div>
                @if (! $editingJd->is_active)
                    <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-950 leading-relaxed">
                        Esta entrada está arquivada. Para voltar ao tablier «em curso», na lista (<strong>Inactivas / arquivadas</strong>) escolha outro estado no combo <strong>Estado</strong> (por exemplo «Em preparação») ou reative e edite texto/CV aqui.
                    </div>
                @endif
                <form method="post" action="{{ route('agents.documents.update', [$agent, $editingJd]) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="trail_return" value="career_trail_ats">
                    @if ($jdListFilter !== AgentsDocumentTrailListFilter::OPEN)
                        <input type="hidden" name="jd_list_filter" value="{{ $jdListFilter }}">
                    @endif
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Título da Vaga</label>
                        <input type="text" name="title" value="{{ old('title', $editingJd->title) }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="Ex.: Engenheiro de software">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Curriculum</label>
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
                @if ($jdListFilter !== AgentsDocumentTrailListFilter::INACTIVE)
                <h3 class="mb-3 text-lg font-semibold">Nova vaga (JD)</h3>
                <p class="mb-3 text-sm text-gray-600">Preencha para adicionar uma nova vaga, ou escolha uma vaga na lista acima para editar.</p>
                <form method="post" action="{{ route('agents.documents.store', $agent) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="trail_return" value="career_trail_ats">
                    @if ($jdListFilter !== AgentsDocumentTrailListFilter::OPEN)
                        <input type="hidden" name="jd_list_filter" value="{{ $jdListFilter }}">
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
                @else
                    <p class="text-sm text-gray-600">Para criar uma nova vaga, mude o filtro <strong>Mostrar na lista</strong> para «Em curso» ou «Todas as activas», ou <a href="{{ route('career-trail.ats') }}#sec-ats-jd-form" class="font-semibold text-indigo-700 hover:underline">abra a vista por defeito</a>.</p>
                @endif
            @endif
        </section>
    @endif

    @if (! $trailReturnCareerTrailAts)
        <section class="rounded-lg border border-gray-200 p-4">
            <h3 class="mb-3 text-lg font-semibold">Vagas (JD) — editar ou actualizar estado</h3>
            <p class="mb-3 text-sm text-gray-600">Altere texto e CV aqui; o <strong>Estado</strong> da candidatura faz-se pela combo ao lado.</p>
            @if ($jds->isEmpty())
                <p class="text-sm text-gray-500">Nenhuma vaga neste filtro. Ajuste o menu «Lista de vagas» em cima (ex.: inactivas / todas as activas).</p>
            @else
                <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200">
                    @foreach ($jds as $jd)
                        @php
                            $hubStatusKey = AgentsDocumentTrailListFilter::listStatusKey($jd->is_active, $jd->application_status);
                            $prepCountHub = (int) ($jd->interview_preparations_count ?? 0);
                            $jdNeedsInterviewArchiveWarnHub =
                                $prepCountHub > 0
                                || $jd->application_status === JobApplicationStatus::Interviewing
                                || (bool) ($jd->interview_processes_exists ?? false);
                        @endphp
                        <li class="space-y-3 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <span class="font-medium">{{ $jd->title ?: 'Vaga #'.$jd->id }}</span>
                                    @if ($jd->userCv)
                                        <p class="mt-1 text-xs text-gray-500">CV associado: {{ ($jd->userCv->title ?: 'CV do perfil #'.$jd->userCv->id).($jd->userCv->is_default ? ' — padrão' : '') }}</p>
                                    @endif
                                </div>
                                <form method="post" action="{{ route('agents.documents.trail-desired-status', [$agent, $jd]) }}" class="w-full shrink-0 sm:w-72">
                                    @csrf
                                    @if ($jdListFilter !== AgentsDocumentTrailListFilter::OPEN)
                                        <input type="hidden" name="jd_list_filter" value="{{ $jdListFilter }}">
                                    @endif
                                    @if ($interviewPrepAgent)
                                        <input type="hidden" name="interview_prep_agent_id" value="{{ $interviewPrepAgent->id }}">
                                    @endif
                                    <label class="mb-1 block text-xs font-medium text-gray-600">Estado</label>
                                    <select name="desired_status" data-prev="{{ $hubStatusKey }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm" title="Alterar estado da candidatura"
                                            onchange="window.trailJdHubStatusPick(this, {{ json_encode([
                                                'needsInterviewArchiveWarn' => $jdNeedsInterviewArchiveWarnHub,
                                                'archiveInterview' => $jdArchiveInterviewConfirm,
                                                'dnp' => $jdDnpConfirm,
                                                'submitted' => 'Confirmar registo do alinhamento ATS (CV+vaga enviados ao assistente)?',
                                                'cvSent' => 'Confirmar que enviou o CV à empresa ou recrutador e está a aguardar retorno?',
                                                'accepted' => 'Marcar esta candidatura como «Aceite»? Só faz sentido com acordo já alcançado no processo.',
                                            ]) }});">
                                        <option value="{{ JobApplicationStatus::Draft->value }}" @selected($hubStatusKey === JobApplicationStatus::Draft->value)>{{ JobApplicationStatus::Draft->label() }}</option>
                                        <option value="{{ JobApplicationStatus::Submitted->value }}" @selected($hubStatusKey === JobApplicationStatus::Submitted->value)>{{ JobApplicationStatus::Submitted->label() }}</option>
                                        <option value="{{ JobApplicationStatus::CvSent->value }}" @selected($hubStatusKey === JobApplicationStatus::CvSent->value)>{{ JobApplicationStatus::CvSent->label() }}</option>
                                        <option value="{{ JobApplicationStatus::Interviewing->value }}" @disabled(! $interviewPrepAgent || ! $canAccessInterviewPrep) @selected($hubStatusKey === JobApplicationStatus::Interviewing->value)>{{ JobApplicationStatus::Interviewing->label() }}</option>
                                        <option value="{{ JobApplicationStatus::Accepted->value }}" @selected($hubStatusKey === JobApplicationStatus::Accepted->value)>{{ JobApplicationStatus::Accepted->label() }}</option>
                                        <option value="{{ JobApplicationStatus::DidNotProceed->value }}" @selected($hubStatusKey === JobApplicationStatus::DidNotProceed->value)>{{ JobApplicationStatus::DidNotProceed->label() }}</option>
                                        <option value="{{ AgentsDocumentTrailListFilter::INACTIVE }}" @selected($hubStatusKey === AgentsDocumentTrailListFilter::INACTIVE)>Inactiva / arquivada</option>
                                    </select>
                                </form>
                            </div>
                            <form method="post" action="{{ route('agents.documents.update', [$agent, $jd]) }}" class="space-y-2">
                                @csrf
                                @method('PUT')
                                @if ($jdListFilter !== AgentsDocumentTrailListFilter::OPEN)
                                    <input type="hidden" name="jd_list_filter" value="{{ $jdListFilter }}">
                                @endif
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
        <script>
            window.trailJdHubStatusPick = function (sel, messages) {
                var v = sel.value;
                var prev = sel.getAttribute('data-prev');
                if (v === prev) return;
                if (v === 'inactive') {
                    if (messages.needsInterviewArchiveWarn && !window.confirm(messages.archiveInterview)) {
                        sel.value = prev;
                        return;
                    }
                    sel.form.submit();
                    return;
                }
                if (v === '{{ JobApplicationStatus::DidNotProceed->value }}') {
                    if (!window.confirm(messages.dnp)) { sel.value = prev; return; }
                }
                if (v === '{{ JobApplicationStatus::Submitted->value }}') {
                    if (!window.confirm(messages.submitted)) { sel.value = prev; return; }
                }
                if (v === '{{ JobApplicationStatus::CvSent->value }}') {
                    if (!window.confirm(messages.cvSent)) { sel.value = prev; return; }
                }
                if (v === '{{ JobApplicationStatus::Accepted->value }}') {
                    if (!window.confirm(messages.accepted)) { sel.value = prev; return; }
                }
                sel.form.submit();
            };
        </script>
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
