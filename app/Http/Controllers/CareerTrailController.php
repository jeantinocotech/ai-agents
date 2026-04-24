<?php

namespace App\Http\Controllers;

use App\Models\CareerTrailStep;
use App\Models\UserCareerTrailProgress;
use App\Services\CareerTrailProgressService;
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

        return view('career-trail.index', [
            'steps' => $bundle['steps'],
            'progress' => $bundle['progress'],
            'currentStep' => $bundle['current'],
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

        $progress->current_step_id = $next->id;
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
