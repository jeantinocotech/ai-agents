<?php

namespace App\Http\Controllers;

use App\Models\AgentDocument;
use App\Models\AtsAnalysis;
use App\Models\CareerTrailStep;
use App\Services\CareerTrailAgentAccess;
use App\Services\CareerTrailProgressService;
use App\Support\AgentsDocumentLibraryViewData;
use App\Support\AgentsDocumentTrailListFilter;
use App\Support\CareerTrailStepCompletion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerTrailController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $bundle = CareerTrailProgressService::ensureProgress($user);
        abort_if($bundle === null, 503, 'Trilha não configurada. Execute o seeder CareerTrailStepsSeeder.');

        $user->refresh();

        $atsTrailAgent = $bundle['steps']->firstWhere('slug', 'ats')?->resolvedAgent();
        $atsAllowsCheck = $atsTrailAgent !== null
            && $atsTrailAgent->is_active
            && CareerTrailStepCompletion::hasAtsCvJdPair($user, $atsTrailAgent);

        return view('career-trail.index', [
            'steps' => $bundle['steps'],
            'progress' => $bundle['progress'],
            'maxReached' => $bundle['maxReached'],
            'atsStepAgent' => $atsTrailAgent,
            'atsAllowsCheck' => $atsAllowsCheck,
            'coverLetterStepAgent' => $bundle['steps']->firstWhere('slug', 'cover-letter')?->resolvedAgent(),
            'interviewStepAgent' => $bundle['steps']->firstWhere('slug', 'interviews')?->resolvedAgent(),
            'cvCreatorChatUrl' => CareerTrailStep::cvEmbeddedCreatorChatUrl(),
        ]);
    }

    public function ats(Request $request): View
    {
        $user = $request->user();
        $bundle = CareerTrailProgressService::ensureProgress($user);
        abort_if($bundle === null, 503, 'Trilha não configurada. Execute o seeder CareerTrailStepsSeeder.');

        $atsStep = $bundle['steps']->firstWhere('slug', 'ats');
        abort_if(! $atsStep, 404);

        $agent = $atsStep->resolvedAgent();
        $agentActive = $agent !== null && $agent->is_active;
        $atsChatBackendReady = $agent !== null && (
            ($agent->isChatKitWorkflow() && trim((string) ($agent->chatkit_workflow_id ?? '')) !== '')
            || (! $agent->isChatKitWorkflow() && $agent->steps()->exists())
            || (! $agent->isChatKitWorkflow() && trim((string) ($agent->assistant_id ?? '')) !== '')
        );
        $atsCvJdPairOk = $agentActive && $agent !== null && CareerTrailStepCompletion::hasAtsCvJdPair($user, $agent);
        $atsAllowsCheck = $agentActive && $agent !== null && $atsChatBackendReady && $atsCvJdPairOk;

        $libraryPayload = [];
        $editingJd = null;
        $atsAnalyzeChatUrl = null;
        $interviewPrepAgent = $bundle['steps']->firstWhere('slug', 'interviews')?->resolvedAgent();
        $canAccessInterviewPrep = $interviewPrepAgent !== null
            && CareerTrailAgentAccess::userCanAccessTrailAgent($user, $interviewPrepAgent);

        if ($agentActive && $agent) {
            CareerTrailAgentAccess::abortUnlessCanAccess($user, $agent);
            $jdListFilter = AgentsDocumentTrailListFilter::fromQuery($request->query('jd_list_filter'));
            $libraryPayload = AgentsDocumentLibraryViewData::payload($user, $agent);

            if ($jdListFilter === AgentsDocumentTrailListFilter::INACTIVE) {
                $visible = $libraryPayload['inactiveJds'] ?? collect();
                $libraryPayload['jds'] = $visible;
                $libraryPayload['jdListTotalCount'] = $visible->count();
                $libraryPayload['jdListVisibleCount'] = $visible->count();
            } else {
                $active = $libraryPayload['activeJds'] ?? collect();
                $filtered = AgentsDocumentTrailListFilter::filterActiveJds($active, $jdListFilter);
                $libraryPayload['jds'] = $filtered;
                $libraryPayload['jdListTotalCount'] = $active->count();
                $libraryPayload['jdListVisibleCount'] = $filtered->count();
            }

            $libraryPayload['jdListFilter'] = $jdListFilter;

            $editJdId = (int) $request->query('edit_jd', 0);
            if ($editJdId > 0) {
                $editingJd = AgentDocument::query()
                    ->whereKey($editJdId)
                    ->where('user_id', $user->id)
                    ->where('agent_id', $agent->id)
                    ->where('type', AgentDocument::TYPE_JD)
                    ->with('userCv:id,title,is_default')
                    ->first();
            }
            if ($editingJd !== null && $atsAllowsCheck) {
                $atsAnalyzeChatUrl = CareerTrailStep::atsAnalyzeChatUrlForJd($user, $agent, (int) $editingJd->id);
            }

            $jdIds = collect($libraryPayload['jds'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
            $atsAnalysisByJd = $jdIds === []
                ? collect()
                : AtsAnalysis::query()
                    ->where('user_id', $user->id)
                    ->whereIn('agent_document_id', $jdIds)
                    ->get(['id', 'user_id', 'agent_document_id', 'user_cv_id', 'ats_score', 'source'])
                    ->keyBy('agent_document_id');
            $libraryPayload['atsAnalysisByJd'] = $atsAnalysisByJd;
        }

        return view('career-trail.ats', [
            'atsStep' => $atsStep,
            'atsAgent' => $agent,
            'atsAgentActive' => $agentActive,
            'atsAllowsCheck' => $atsAllowsCheck,
            'atsBackendMisconfigured' => $agentActive && $agent !== null && $atsCvJdPairOk && ! $atsChatBackendReady,
            'libraryPayload' => $libraryPayload,
            'editingJd' => $editingJd,
            'atsAnalyzeChatUrl' => $atsAnalyzeChatUrl,
            'interviewPrepAgent' => $interviewPrepAgent,
            'canAccessInterviewPrep' => $canAccessInterviewPrep,
        ]);
    }
}
