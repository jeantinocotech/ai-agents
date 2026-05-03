<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\MotivationLetter;
use App\Services\CareerTrailAgentAccess;
use App\Services\ChatKitDocumentLibraryService;
use App\Support\CareerTrailAtsJdValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MotivationLetterController extends Controller
{
    public function index(Request $request, Agent $agent): View
    {
        $this->authorizeCoverLetterAgent($agent);

        $search = trim((string) $request->query('q', ''));

        $lettersQuery = MotivationLetter::query()
            ->where('user_id', Auth::id())
            ->with(['jdDocument' => fn ($q) => $q->with('userCv')])
            ->orderByDesc('updated_at');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $lettersQuery->where(function ($w) use ($like) {
                $w->where('title', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhereHas('jdDocument', function ($jq) use ($like) {
                        $jq->where('title', 'like', $like)
                            ->orWhere('body', 'like', $like);
                    });
            });
        }

        $letters = $lettersQuery->paginate(12)->withQueryString();

        $library = ChatKitDocumentLibraryService::forUserAndAgent((int) Auth::id(), $agent);
        $jdOptions = $library['jds'] ?? [];
        $hubAgentId = (int) ($library['documents_hub_agent_id'] ?? $agent->id);
        $documentsHubUrl = CareerTrailAgentAccess::documentsHubUrl(Agent::query()->find($hubAgentId) ?? $agent);

        return view('agents.motivation-letters.index', [
            'agent' => $agent,
            'letters' => $letters,
            'search' => $search,
            'jdOptions' => $jdOptions,
            'documentsHubUrl' => $documentsHubUrl,
        ]);
    }

    public function create(Request $request, Agent $agent): View
    {
        $this->authorizeCoverLetterAgent($agent);

        $library = ChatKitDocumentLibraryService::forUserAndAgent((int) Auth::id(), $agent);
        $jdOptions = $library['jds'] ?? [];
        $hubAgentId = (int) ($library['documents_hub_agent_id'] ?? $agent->id);
        $documentsHubUrl = CareerTrailAgentAccess::documentsHubUrl(Agent::query()->find($hubAgentId) ?? $agent);
        $preselectJd = $request->query('jd_document_id');

        return view('agents.motivation-letters.create', [
            'agent' => $agent,
            'jdOptions' => $jdOptions,
            'documentsHubUrl' => $documentsHubUrl,
            'preselectJd' => $preselectJd !== null ? (string) $preselectJd : null,
        ]);
    }

    public function edit(Agent $agent, MotivationLetter $motivationLetter): View
    {
        $this->authorizeCoverLetterAgent($agent);
        $this->authorizeLetterOwnership($motivationLetter);

        $motivationLetter->load(['jdDocument' => fn ($q) => $q->with('userCv')]);

        return view('agents.motivation-letters.edit', [
            'agent' => $agent,
            'letter' => $motivationLetter,
        ]);
    }

    public function store(Request $request, Agent $agent): RedirectResponse
    {
        $this->authorizeCoverLetterAgent($agent);

        $validated = $request->validate([
            'jd_document_id' => ['required', 'integer', Rule::exists('agent_documents', 'id')],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'source' => ['sometimes', 'string', Rule::in([MotivationLetter::SOURCE_MANUAL, MotivationLetter::SOURCE_GENERATED])],
        ]);

        $jd = CareerTrailAtsJdValidator::validatedJdForUser((int) $validated['jd_document_id'], $request->user());

        MotivationLetter::query()->updateOrCreate(
            [
                'user_id' => (int) $request->user()->id,
                'jd_document_id' => (int) $jd->id,
            ],
            [
                'title' => $validated['title'] ?? null,
                'body' => $validated['body'],
                'source' => $validated['source'] ?? MotivationLetter::SOURCE_MANUAL,
            ]
        );

        return redirect()
            ->route('agents.motivation-letters.index', $agent)
            ->with('status', 'Carta de motivação guardada para este processo (CV + vaga).');
    }

    public function update(Request $request, Agent $agent, MotivationLetter $motivationLetter): RedirectResponse
    {
        $this->authorizeCoverLetterAgent($agent);
        $this->authorizeLetterOwnership($motivationLetter);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'source' => ['sometimes', 'string', Rule::in([MotivationLetter::SOURCE_MANUAL, MotivationLetter::SOURCE_GENERATED])],
        ]);

        $motivationLetter->title = $validated['title'] ?? null;
        $motivationLetter->body = $validated['body'];
        if ($request->has('source')) {
            $motivationLetter->source = $validated['source'];
        }
        $motivationLetter->save();

        return redirect()
            ->route('agents.motivation-letters.index', $agent)
            ->with('status', 'Carta atualizada.');
    }

    public function destroy(Agent $agent, MotivationLetter $motivationLetter): RedirectResponse
    {
        $this->authorizeCoverLetterAgent($agent);
        $this->authorizeLetterOwnership($motivationLetter);

        $motivationLetter->delete();

        return redirect()
            ->route('agents.motivation-letters.index', $agent)
            ->with('status', 'Carta removida.');
    }

    private function authorizeCoverLetterAgent(Agent $agent): void
    {
        CareerTrailAgentAccess::abortUnlessCanAccess(Auth::user(), $agent);
        $step = CareerTrailAgentAccess::trailStepBoundToAgent($agent);
        abort_if($step === null || $step->slug !== 'cover-letter', 403);
    }

    private function authorizeLetterOwnership(MotivationLetter $letter): void
    {
        abort_unless((int) $letter->user_id === (int) Auth::id(), 403);
    }
}
