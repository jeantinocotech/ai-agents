<?php

namespace App\Http\Controllers;

use App\Models\CareerTrailStep;
use App\Models\UserCareerTrailProgress;
use App\Services\CareerTrailAgentAccess;
use App\Services\CareerTrailProgressService;
use App\Support\AgentsDocumentLibraryViewData;
use App\Support\CareerTrailStepCompletion;
use Illuminate\Http\RedirectResponse;
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

        $currentStep = $bundle['current'];
        $atsTrailAgent = $bundle['steps']->firstWhere('slug', 'ats')?->resolvedAgent();
        $atsAllowsCheck = $atsTrailAgent !== null
            && $atsTrailAgent->is_active
            && CareerTrailStepCompletion::hasAtsCvJdPair($user, $atsTrailAgent);

        return view('career-trail.index', [
            'steps' => $bundle['steps'],
            'progress' => $bundle['progress'],
            'currentStep' => $currentStep,
            'currentStepReadiness' => CareerTrailStepCompletion::readiness($user, $currentStep),
            'currentStepChecklist' => CareerTrailStepCompletion::checklist($user, $currentStep),
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
        );
        $atsCvJdPairOk = $agentActive && $agent !== null && CareerTrailStepCompletion::hasAtsCvJdPair($user, $agent);
        $atsAllowsCheck = $agentActive && $agent !== null && $atsChatBackendReady && $atsCvJdPairOk;

        $libraryPayload = [];
        if ($agentActive && $agent) {
            CareerTrailAgentAccess::abortUnlessCanAccess($user, $agent);
            $libraryPayload = AgentsDocumentLibraryViewData::payload($user, $agent);
        }

        return view('career-trail.ats', [
            'atsStep' => $atsStep,
            'atsAgent' => $agent,
            'atsAgentActive' => $agentActive,
            'atsAllowsCheck' => $atsAllowsCheck,
            'atsBackendMisconfigured' => $agentActive && $agent !== null && $atsCvJdPairOk && ! $atsChatBackendReady,
            'readiness' => CareerTrailStepCompletion::readiness($user, $atsStep),
            'checklist' => CareerTrailStepCompletion::checklist($user, $atsStep),
            'libraryPayload' => $libraryPayload,
        ]);
    }

    public function advance(Request $request): RedirectResponse
    {
        $user = $request->user();
        $progress = UserCareerTrailProgress::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $current = $progress->currentStep;
        if (! $current) {
            return redirect()->route('career-trail.index')
                ->with('error', 'Estado da trilha inválido.');
        }

        $next = CareerTrailStep::query()
            ->where('is_active', true)
            ->where('sort_order', '>', $current->sort_order)
            ->orderBy('sort_order')
            ->first();

        if (! $next) {
            return redirect()->route('career-trail.index')
                ->with('info', 'Já está na última etapa da trilha.');
        }

        $gate = CareerTrailStepCompletion::readiness($user, $current);
        if (! $gate['ready']) {
            return redirect()->route('career-trail.index')
                ->with('error', $gate['blocked_message'] ?? 'Complete os requisitos desta etapa antes de avançar.');
        }

        if ($current->slug === 'ats') {
            $landingStep = CareerTrailStep::landingStepAfterCompletedAts();
            if (! $landingStep) {
                return redirect()->route('career-trail.index')
                    ->with('error', 'Configuração da trilha incompleta.');
            }

            $motivationStep = CareerTrailStep::query()
                ->where('is_active', true)
                ->where('slug', 'cover-letter')
                ->first();
            $interviewStep = CareerTrailStep::query()
                ->where('is_active', true)
                ->where('slug', 'interviews')
                ->first();

            $newMax = (int) ($progress->max_sort_order_reached ?? 0);
            foreach ([$motivationStep, $interviewStep] as $stepUnlock) {
                if ($stepUnlock !== null) {
                    $newMax = max($newMax, (int) $stepUnlock->sort_order);
                }
            }

            $progress->current_step_id = $landingStep->id;
            $progress->max_sort_order_reached = $newMax;
            $progress->save();

            return redirect()->route('career-trail.index')
                ->with('status', 'Motivação (opcional) e Entrevista estão desbloqueadas. Etapa sugerida: '.$landingStep->title.'.');
        }

        $progress->current_step_id = $next->id;
        $progress->max_sort_order_reached = max(
            (int) ($progress->max_sort_order_reached ?? 0),
            (int) $next->sort_order
        );
        $progress->save();

        return redirect()->route('career-trail.index')
            ->with('status', 'Avançou para: '.$next->title);
    }

    public function back(Request $request): RedirectResponse
    {
        $user = $request->user();
        $progress = UserCareerTrailProgress::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $current = $progress->currentStep;
        if (! $current) {
            return redirect()->route('career-trail.index')
                ->with('error', 'Estado da trilha inválido.');
        }

        $prev = CareerTrailStep::query()
            ->where('is_active', true)
            ->where('sort_order', '<', $current->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if (! $prev) {
            return redirect()->route('career-trail.index')
                ->with('info', 'Já está na primeira etapa.');
        }

        $progress->current_step_id = $prev->id;
        $progress->save();

        return redirect()->route('career-trail.index')
            ->with('status', 'Voltou para: '.$prev->title);
    }
}
