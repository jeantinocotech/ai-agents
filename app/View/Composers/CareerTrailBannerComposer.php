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
        $bundle = CareerTrailProgressService::ensureProgress($user);
        if ($bundle === null) {
            $view->with('careerTrailContext', null);

            return;
        }

        /** @var \App\Models\CareerTrailStep $current */
        $current = $bundle['current'];
        $steps = $bundle['steps'];

        $readiness = CareerTrailStepCompletion::readiness($user, $current);
        $nextStep = CareerTrailStep::query()
            ->where('is_active', true)
            ->where('sort_order', '>', $current->sort_order)
            ->orderBy('sort_order')
            ->first();

        $suggestAdvance = $readiness['ready'] === true
            && $readiness['reason'] !== null
            && $nextStep !== null;

        $gracaBody = trim((string) ($current->graca_guidance ?? ''));
        if ($gracaBody === '') {
            $gracaBody = trim((string) ($current->short_description ?? ''));
        }

        $view->with('careerTrailContext', [
            'steps' => $steps,
            'current' => $current,
            'gracaBody' => $gracaBody,
            'suggestAdvance' => $suggestAdvance,
            'advanceReason' => $readiness['reason'],
            'nextStepTitle' => $nextStep?->title,
        ]);
    }
}
