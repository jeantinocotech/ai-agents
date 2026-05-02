<?php

namespace App\View\Composers;

use App\Models\CareerTrailStep;
use App\Services\CareerTrailProgressService;
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

        /** @var \App\Models\CareerTrailStep $current */
        $current = $bundle['current'];
        $steps = $bundle['steps'];

        $progress = $bundle['progress'];
        $maxReached = (int) ($progress->max_sort_order_reached ?? $current->sort_order);

        $view->with('careerTrailContext', [
            'user' => $user,
            'steps' => $steps,
            'current' => $current,
            'maxReached' => $maxReached,
            'tokenBalance' => (int) $user->token_balance,
            'atsStepAgent' => $steps->firstWhere('slug', 'ats')?->resolvedAgent(),
            'cvCreatorChatUrl' => CareerTrailStep::cvEmbeddedCreatorChatUrl(),
        ]);
    }
}
