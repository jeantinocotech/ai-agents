<?php

namespace App\View\Composers;

use App\Models\CareerTrailStep;
use App\Services\CareerTrailProgressService;
use App\Support\CareerTrailStepCompletion;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class CareerTrailBannerComposer
{
    public function compose(View $view): void
    {
        if (! Auth::check()) {
            $view->with('careerTrailContext', null);

            return;
        }

        if (request()->routeIs('admin.*')) {
            $view->with('careerTrailContext', null);

            return;
        }

        $user = Auth::user();
        $user->refresh();
        $bundle = CareerTrailProgressService::ensureProgress($user);
        if ($bundle === null) {
            $view->with('careerTrailContext', null);

            return;
        }

        $steps = $bundle['steps'];
        $maxReached = $bundle['maxReached'];
        $frontierStep = $bundle['frontierStep'];

        $atsTrailAgentForBanner = $steps->firstWhere('slug', 'ats')?->resolvedAgent();
        $atsAllowsCheck = $atsTrailAgentForBanner !== null
            && $atsTrailAgentForBanner->is_active
            && CareerTrailStepCompletion::hasAtsCvJdPair($user, $atsTrailAgentForBanner);

        $view->with('careerTrailContext', [
            'user' => $user,
            'steps' => $steps,
            'maxReached' => $maxReached,
            'frontierStep' => $frontierStep,
            'tokenBalance' => (int) $user->token_balance,
            'atsStepAgent' => $atsTrailAgentForBanner,
            'atsAllowsCheck' => $atsAllowsCheck,
            'cvCreatorChatUrl' => CareerTrailStep::cvEmbeddedCreatorChatUrl(),
        ]);
    }
}
