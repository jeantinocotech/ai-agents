<?php

namespace App\Http\Controllers;

use App\Enums\InterviewApplicationOutcome;
use App\Enums\InterviewPersona;
use App\Enums\InterviewProcessStatus;
use App\Models\Agent;
use App\Models\InterviewPreparation;
use App\Models\InterviewProcess;
use App\Services\CareerTrailAgentAccess;
use App\Services\ChatKitDocumentLibraryService;
use App\Services\InterviewProcessOutcomeService;
use App\Support\CareerTrailAtsJdValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InterviewPreparationController extends Controller
{
    public function index(Request $request, Agent $agent): View
    {
        $this->authorizeInterviewStepAgent($agent);

        $userId = (int) Auth::id();
        $search = trim((string) $request->query('q', ''));

        $showAllInterviewStatuses = $request->boolean('all_statuses');

        $personaInputs = Arr::wrap($request->query('personas'));
        if ($request->filled('persona') && $personaInputs === []) {
            $personaInputs = [(string) $request->query('persona')];
        }

        $statusInputs = Arr::wrap($request->query('statuses'));
        if ($request->filled('status') && $statusInputs === []) {
            $statusInputs = [(string) $request->query('status')];
        }

        $personaValuesIndexed = [];
        foreach ($personaInputs as $raw) {
            $p = InterviewPersona::tryFrom(trim((string) $raw));
            if ($p instanceof InterviewPersona) {
                $personaValuesIndexed[$p->value] = true;
            }
        }
        $personaValues = array_keys($personaValuesIndexed);
        sort($personaValues);

        $statusValuesIndexed = [];
        foreach ($statusInputs as $raw) {
            $s = InterviewProcessStatus::tryFrom(trim((string) $raw));
            if ($s instanceof InterviewProcessStatus) {
                $statusValuesIndexed[$s->value] = true;
            }
        }
        $statusValuesChosen = array_keys($statusValuesIndexed);
        sort($statusValuesChosen);

        $defaultStatuses = InterviewProcessStatus::activeListingDefaults();

        $applyStatusRestriction = false;
        $statusRestrictionList = [];
        if (! $showAllInterviewStatuses) {
            $applyStatusRestriction = true;
            if ($request->has('statuses')) {
                $statusRestrictionList = $statusValuesChosen === [] ? $defaultStatuses : $statusValuesChosen;
            } else {
                $statusRestrictionList = $defaultStatuses;
            }
        }

        $showAllProcessOutcomes = $request->boolean('all_process_outcomes');

        $processOutcomeInputs = Arr::wrap($request->query('process_outcomes'));
        $allowedProcessOutcomesIndexed = [];
        foreach ($processOutcomeInputs as $raw) {
            $po = InterviewApplicationOutcome::tryFrom(trim((string) $raw));
            if ($po instanceof InterviewApplicationOutcome) {
                $allowedProcessOutcomesIndexed[$po->value] = true;
            }
        }
        $processOutcomesChosen = array_keys($allowedProcessOutcomesIndexed);
        sort($processOutcomesChosen);

        $defaultProcessOutcomes = InterviewApplicationOutcome::defaultListingFilters();

        $applyProcessOutcomeRestriction = false;
        $processOutcomeRestrictionList = [];
        if (! $showAllProcessOutcomes) {
            $applyProcessOutcomeRestriction = true;
            if ($request->has('process_outcomes')) {
                $processOutcomeRestrictionList = $processOutcomesChosen === []
                    ? $defaultProcessOutcomes
                    : $processOutcomesChosen;
            } else {
                $processOutcomeRestrictionList = $defaultProcessOutcomes;
            }
        }

        $filteredBase = $this->filterInterviewPreparationQueryForIndex(
            $userId,
            InterviewPreparation::query()->withoutEagerLoads()->where('user_id', $userId),
            $search,
            $personaValues,
            $applyStatusRestriction,
            $statusRestrictionList,
            $applyProcessOutcomeRestriction,
            $processOutcomeRestrictionList
        );

        $groupAggregation = fn () => (clone $filteredBase)
            ->select('jd_document_id')
            ->selectRaw('MAX(updated_at) AS group_touch')
            ->groupBy('jd_document_id');

        $totalGroups = (int) DB::query()->fromSub($groupAggregation(), 'prep_groups_sq')->count();

        $perPage = 8;
        $currentPage = max(1, (int) $request->input('page', 1));

        $jdIdsForPage = [];
        if ($totalGroups > 0) {
            $jdIdsForPage = DB::query()
                ->fromSub($groupAggregation(), 'prep_groups_pg')
                ->orderByDesc('group_touch')
                ->offset(($currentPage - 1) * $perPage)
                ->limit($perPage)
                ->pluck('jd_document_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $groupsOrdered = [];
        if ($jdIdsForPage !== []) {
            $allRoundsForPage = InterviewPreparation::query()
                ->where('user_id', $userId)
                ->whereIn('jd_document_id', $jdIdsForPage)
                ->with(['jdDocument' => fn ($q) => $q->with('userCv')])
                ->orderBy('sequence')
                ->get();

            $processByJdId = InterviewProcess::query()
                ->where('user_id', $userId)
                ->whereIn('jd_document_id', $jdIdsForPage)
                ->get()
                ->keyBy('jd_document_id');

            foreach ($jdIdsForPage as $jdId) {
                $rounds = $allRoundsForPage
                    ->where('jd_document_id', $jdId)
                    ->sortBy('sequence')
                    ->values();
                if ($rounds->isEmpty()) {
                    continue;
                }

                /** @var \App\Models\AgentDocument|null $jd */
                $jd = $rounds->first()->jdDocument;

                $groupsOrdered[] = [
                    'jd' => $jd,
                    'rounds' => $rounds,
                    'process' => $processByJdId->get($jdId)
                        ?? InterviewProcessOutcomeService::refreshOutcomeFromRounds($userId, $jdId),
                ];
            }
        }

        $processGroups = new LengthAwarePaginator(
            $groupsOrdered,
            $totalGroups,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );
        $processGroups->withQueryString();

        $library = ChatKitDocumentLibraryService::forUserAndAgent($userId, $agent);
        $jdOptions = $library['jds'] ?? [];
        $hubAgentId = (int) ($library['documents_hub_agent_id'] ?? $agent->id);
        $documentsHubUrl = CareerTrailAgentAccess::documentsHubUrl(Agent::query()->find($hubAgentId) ?? $agent);

        $filtersActive = $this->interviewIndexFiltersDeviatedFromDefaults(
            $request,
            $search,
            $personaValues,
            $showAllInterviewStatuses,
            $statusRestrictionList,
            $showAllProcessOutcomes,
            $processOutcomeRestrictionList
        );

        $personasUiSelected = $personaValues;
        $showAllStatusesCheckbox = $showAllInterviewStatuses;
        if ($showAllStatusesCheckbox) {
            $statusesUiSelected = array_column(InterviewProcessStatus::optionsForForms(), 'value');
        } elseif ($request->has('statuses')) {
            $statusesUiSelected = $statusRestrictionList;
        } else {
            $statusesUiSelected = $defaultStatuses;
        }

        $showAllProcessOutcomesCheckbox = $showAllProcessOutcomes;
        if ($showAllProcessOutcomesCheckbox) {
            $processOutcomesSelected = array_column(InterviewApplicationOutcome::optionsForForms(), 'value');
        } elseif ($request->has('process_outcomes')) {
            $processOutcomesSelected = $processOutcomeRestrictionList;
        } else {
            $processOutcomesSelected = $defaultProcessOutcomes;
        }

        return view('agents.interview-preparations.index', [
            'agent' => $agent,
            'processGroups' => $processGroups,
            'search' => $search,
            'personasSelected' => $personasUiSelected,
            'statusesSelected' => $statusesUiSelected,
            'showAllStatusesCheckbox' => $showAllStatusesCheckbox,
            'defaultStatusesValues' => $defaultStatuses,
            'processOutcomeOptions' => InterviewApplicationOutcome::optionsForForms(),
            'processOutcomesSelected' => $processOutcomesSelected,
            'showAllProcessOutcomesCheckbox' => $showAllProcessOutcomesCheckbox,
            'defaultProcessOutcomesValues' => $defaultProcessOutcomes,
            'jdOptions' => $jdOptions,
            'documentsHubUrl' => $documentsHubUrl,
            'personaOptions' => InterviewPersona::optionsForForms(),
            'statusOptions' => InterviewProcessStatus::optionsForForms(),
            'filtersActive' => $filtersActive,
        ]);
    }

    /**
     * @param  Builder<\App\Models\InterviewPreparation>  $query
     * @param  array<int, string>  $personaValues
     * @param  array<int, string>  $statusRestrictionList
     * @param  array<int, string>  $processOutcomeRestrictionList
     * @return Builder<\App\Models\InterviewPreparation>
     */
    private function filterInterviewPreparationQueryForIndex(
        int $userIdScoped,
        Builder $query,
        string $search,
        array $personaValues,
        bool $applyStatusRestriction,
        array $statusRestrictionList,
        bool $applyProcessOutcomeRestriction,
        array $processOutcomeRestrictionList,
    ): Builder {
        if ($personaValues !== []) {
            $query->whereIn('persona', $personaValues);
        }

        if ($applyStatusRestriction && $statusRestrictionList !== []) {
            $query->whereIn('status', $statusRestrictionList);
        }

        if ($applyProcessOutcomeRestriction && $processOutcomeRestrictionList !== []) {
            self::restrictInterviewPrepQueryByProcessOutcomes($query, $userIdScoped, $processOutcomeRestrictionList);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($w) use ($like) {
                $w->where('chat_prep_messages', 'like', $like)
                    ->orWhere('learnings', 'like', $like)
                    ->orWhereHas('jdDocument', function ($jq) use ($like) {
                        $jq->where('title', 'like', $like)
                            ->orWhere('body', 'like', $like);
                    });
            });
        }

        return $query;
    }

    /**
     * @param  array<int, string>  $allowedOutcomes  Enum values InterviewApplicationOutcome
     */
    private static function restrictInterviewPrepQueryByProcessOutcomes(
        Builder $prepQuery,
        int $userId,
        array $allowedOutcomes
    ): void {
        $prepQuery->where(function ($outer) use ($allowedOutcomes, $userId) {
            $first = true;

            foreach ($allowedOutcomes as $o) {
                if (InterviewApplicationOutcome::tryFrom($o) === null) {
                    continue;
                }

                $clause = $first ? 'where' : 'orWhere';
                $first = false;

                if ($o === InterviewApplicationOutcome::Ongoing->value) {
                    $outer->{$clause}(function ($w) use ($userId) {
                        $w->whereNotExists(function ($sub) use ($userId) {
                            $sub->selectRaw('1')
                                ->from('interview_processes as ipc')
                                ->whereColumn('ipc.jd_document_id', 'interview_preparations.jd_document_id')
                                ->where('ipc.user_id', $userId);
                        })->orWhereExists(function ($sub) use ($userId) {
                            $sub->selectRaw('1')
                                ->from('interview_processes as ipc')
                                ->whereColumn('ipc.jd_document_id', 'interview_preparations.jd_document_id')
                                ->where('ipc.user_id', $userId)
                                ->where('ipc.outcome', InterviewApplicationOutcome::Ongoing->value);
                        });
                    });

                    continue;
                }

                $outer->{$clause}(function ($w) use ($userId, $o) {
                    $w->whereExists(function ($sub) use ($userId, $o) {
                        $sub->selectRaw('1')
                            ->from('interview_processes as ipc')
                            ->whereColumn('ipc.jd_document_id', 'interview_preparations.jd_document_id')
                            ->where('ipc.user_id', $userId)
                            ->where('ipc.outcome', $o);
                    });
                });
            }
        });
    }

    public function create(Request $request, Agent $agent): View
    {
        $this->authorizeInterviewStepAgent($agent);

        $library = ChatKitDocumentLibraryService::forUserAndAgent((int) Auth::id(), $agent);
        $jdOptionsRaw = $library['jds'] ?? [];
        $userId = (int) Auth::id();
        $jdOptions = $this->jdOptionsExcludingDidNotProceedProcesses($userId, $jdOptionsRaw);
        $allProcessesClosedForNewEntry = $jdOptionsRaw !== [] && $jdOptions === [];
        $hubAgentId = (int) ($library['documents_hub_agent_id'] ?? $agent->id);
        $documentsHubUrl = CareerTrailAgentAccess::documentsHubUrl(Agent::query()->find($hubAgentId) ?? $agent);

        $preselectJd = $request->query('jd_document_id');
        $effectivePreselect = null;

        $nextRoundByJdId = [];
        foreach ($jdOptions as $opt) {
            $id = (int) ($opt['id'] ?? 0);
            if ($id > 0) {
                $nextRoundByJdId[$id] = $this->nextSequence($userId, $id);
            }
        }

        if ($preselectJd !== null && is_numeric((string) $preselectJd)) {
            $jdId = (int) $preselectJd;
            foreach ($jdOptions as $opt) {
                if ((int) ($opt['id'] ?? 0) === $jdId) {
                    $effectivePreselect = (string) $jdId;

                    break;
                }
            }
        }

        return view('agents.interview-preparations.create', [
            'agent' => $agent,
            'jdOptions' => $jdOptions,
            'nextRoundByJdId' => $nextRoundByJdId,
            'didNotProceedProcessHiddenCount' => max(0, count($jdOptionsRaw) - count($jdOptions)),
            'allProcessesClosedForNewEntry' => $allProcessesClosedForNewEntry,
            'documentsHubUrl' => $documentsHubUrl,
            'preselectJd' => $effectivePreselect,
            'personaOptions' => InterviewPersona::optionsForForms(),
            'statusOptions' => InterviewProcessStatus::optionsForForms(),
        ]);
    }

    public function edit(Agent $agent, InterviewPreparation $interviewPreparation): View
    {
        $this->authorizeInterviewStepAgent($agent);
        $this->authorizeInterviewOwnership($interviewPreparation);

        $interviewPreparation->load(['jdDocument' => fn ($q) => $q->with('userCv')]);

        return view('agents.interview-preparations.edit', [
            'agent' => $agent,
            'prep' => $interviewPreparation,
            'personaOptions' => InterviewPersona::optionsForForms(),
            'statusOptions' => InterviewProcessStatus::optionsForForms(),
        ]);
    }

    public function store(Request $request, Agent $agent): RedirectResponse
    {
        $this->authorizeInterviewStepAgent($agent);

        $validated = $request->validate([
            'jd_document_id' => ['required', 'integer', Rule::exists('agent_documents', 'id')],
            'persona' => ['required', Rule::enum(InterviewPersona::class)],
            'status' => ['required', Rule::enum(InterviewProcessStatus::class)],
            'chat_prep_messages' => ['nullable', 'string'],
            'learnings' => ['nullable', 'string'],
        ]);

        $jd = CareerTrailAtsJdValidator::validatedJdForUser((int) $validated['jd_document_id'], $request->user());

        $processDidNotProceed = InterviewProcess::query()
            ->where('user_id', $request->user()->id)
            ->where('jd_document_id', $jd->id)
            ->where('outcome', InterviewApplicationOutcome::DidNotProceed->value)
            ->exists();
        abort_if($processDidNotProceed, 422, 'Este processo está marcado como "Não prosseguiu"; não é possível registrar novas rondas.');

        $sequence = $this->nextSequence((int) $request->user()->id, (int) $jd->id);

        $exists = InterviewPreparation::query()
            ->where('user_id', $request->user()->id)
            ->where('jd_document_id', $jd->id)
            ->where('sequence', $sequence)
            ->exists();
        abort_if($exists, 422, 'Já existe uma entrevista com esta sequência para este processo (vaga).');

        InterviewPreparation::query()->create([
            'user_id' => (int) $request->user()->id,
            'jd_document_id' => (int) $jd->id,
            'sequence' => $sequence,
            'persona' => $validated['persona'],
            'status' => $validated['status'],
            'chat_prep_messages' => $validated['chat_prep_messages'] ?? null,
            'learnings' => $validated['learnings'] ?? null,
        ]);

        return redirect()
            ->route('agents.interview-preparations.index', $agent)
            ->with('status', 'Entrevista registrada.');
    }

    public function update(Request $request, Agent $agent, InterviewPreparation $interviewPreparation): RedirectResponse
    {
        $this->authorizeInterviewStepAgent($agent);
        $this->authorizeInterviewOwnership($interviewPreparation);

        $rules = [
            'persona' => ['required', Rule::enum(InterviewPersona::class)],
            'status' => ['required', Rule::enum(InterviewProcessStatus::class)],
        ];
        if ($request->has('chat_prep_messages')) {
            $rules['chat_prep_messages'] = ['nullable', 'string'];
        }
        if ($request->has('learnings')) {
            $rules['learnings'] = ['nullable', 'string'];
        }
        $validated = $request->validate($rules);

        $interviewPreparation->persona = $validated['persona'];
        $interviewPreparation->status = $validated['status'];
        if (array_key_exists('chat_prep_messages', $validated)) {
            $interviewPreparation->chat_prep_messages = $validated['chat_prep_messages'];
        }
        if (array_key_exists('learnings', $validated)) {
            $interviewPreparation->learnings = $validated['learnings'];
        }
        $interviewPreparation->save();

        return redirect()
            ->route('agents.interview-preparations.index', $agent)
            ->with('status', 'Entrevista atualizada.');
    }

    public function destroy(Agent $agent, InterviewPreparation $interviewPreparation): RedirectResponse
    {
        $this->authorizeInterviewStepAgent($agent);
        $this->authorizeInterviewOwnership($interviewPreparation);

        $interviewPreparation->delete();

        return redirect()
            ->route('agents.interview-preparations.index', $agent)
            ->with('status', 'Registro removido.');
    }

    /**
     * @param  list<array<string, mixed>>  $jdOptions
     * @return list<array<string, mixed>>
     */
    private function jdOptionsExcludingDidNotProceedProcesses(int $userId, array $jdOptions): array
    {
        if ($jdOptions === []) {
            return [];
        }

        $closedIds = InterviewProcess::query()
            ->where('user_id', $userId)
            ->where('outcome', InterviewApplicationOutcome::DidNotProceed->value)
            ->pluck('jd_document_id')
            ->all();

        if ($closedIds === []) {
            return $jdOptions;
        }

        $closed = array_fill_keys(array_map('intval', $closedIds), true);

        return array_values(array_filter($jdOptions, function (array $opt) use ($closed): bool {
            $id = (int) ($opt['id'] ?? 0);

            return $id > 0 && ! isset($closed[$id]);
        }));
    }

    private function nextSequence(int $userId, int $jdId): int
    {
        $max = InterviewPreparation::query()
            ->where('user_id', $userId)
            ->where('jd_document_id', $jdId)
            ->max('sequence');

        return (int) $max + 1;
    }

    private function authorizeInterviewStepAgent(Agent $agent): void
    {
        CareerTrailAgentAccess::abortUnlessCanAccess(Auth::user(), $agent);
        $step = CareerTrailAgentAccess::trailStepBoundToAgent($agent);
        abort_if($step === null || $step->slug !== 'interviews', 403);
    }

    private function authorizeInterviewOwnership(InterviewPreparation $prep): void
    {
        abort_unless((int) $prep->user_id === (int) Auth::id(), 403);
    }

    /**
     * True quando o utilizador saiu da combinação por omissão ao abrir o ecrã:
     * sem pesquisa, sem personas, estados de ronda = em processo + avançou,
     * resultado global = em curso + aprovado, sem interruptores «mostrar tudo».
     */
    private function interviewIndexFiltersDeviatedFromDefaults(
        Request $request,
        string $search,
        array $personaValues,
        bool $showAllInterviewStatuses,
        array $statusRestrictionList,
        bool $showAllProcessOutcomes,
        array $processOutcomeRestrictionList
    ): bool {
        if ($search !== '') {
            return true;
        }

        if ($personaValues !== [] || $request->filled('persona')) {
            return true;
        }

        if ($showAllInterviewStatuses || $showAllProcessOutcomes) {
            return true;
        }

        if (! $showAllInterviewStatuses) {
            $canonical = self::sortedUniqueValues(InterviewProcessStatus::activeListingDefaults());
            $effective = self::sortedUniqueValues($statusRestrictionList);

            if ($effective !== $canonical) {
                return true;
            }
        }

        $canonicalOutcomes = self::sortedUniqueValues(InterviewApplicationOutcome::defaultListingFilters());
        $effectiveOutcomes = self::sortedUniqueValues($processOutcomeRestrictionList);

        return $effectiveOutcomes !== $canonicalOutcomes;
    }

    /**
     * @param  array<int, string|mixed>  $values
     * @return list<string>
     */
    private static function sortedUniqueValues(array $values): array
    {
        $normalized = [];
        foreach ($values as $v) {
            $normalized[] = (string) $v;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }
}
