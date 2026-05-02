<?php

namespace App\Http\Controllers;

use App\Enums\InterviewApplicationOutcome;
use App\Models\Agent;
use App\Models\AgentDocument;
use App\Services\CareerTrailAgentAccess;
use App\Services\InterviewProcessOutcomeService;
use App\Support\CareerTrailAtsJdValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InterviewProcessOutcomeController extends Controller
{
    public function update(Request $request, Agent $agent, AgentDocument $jdDocument): RedirectResponse
    {
        CareerTrailAgentAccess::abortUnlessCanAccess(Auth::user(), $agent);

        $step = CareerTrailAgentAccess::trailStepBoundToAgent($agent);
        abort_if($step === null || $step->slug !== 'interviews', 403);

        abort_unless(
            $jdDocument->type === AgentDocument::TYPE_JD && (int) $jdDocument->user_id === (int) Auth::id(),
            404
        );

        CareerTrailAtsJdValidator::validatedJdForUser((int) $jdDocument->getKey(), $request->user());

        $validated = $request->validate([
            'outcome' => [
                'required',
                Rule::in([
                    InterviewApplicationOutcome::Approved->value,
                    InterviewApplicationOutcome::Ongoing->value,
                ]),
            ],
        ]);

        if ($validated['outcome'] === InterviewApplicationOutcome::Approved->value) {
            InterviewProcessOutcomeService::approveForUser((int) Auth::id(), (int) $jdDocument->getKey());
        } else {
            InterviewProcessOutcomeService::reopenToOngoing((int) Auth::id(), (int) $jdDocument->getKey());
        }

        return redirect()
            ->route('agents.interview-preparations.index', $agent)
            ->with('status', 'Estado da candidatura atualizado.');
    }
}
