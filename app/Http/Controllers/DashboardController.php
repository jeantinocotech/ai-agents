<?php

namespace App\Http\Controllers;

use App\Enums\InterviewApplicationOutcome;
use App\Models\InterviewProcess;
use App\Services\CareerTrailProgressService;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, GamificationService $gamification): View
    {
        $user = $request->user();
        $bundle = CareerTrailProgressService::ensureProgress($user);
        abort_if($bundle === null, 503, 'Trilha não configurada.');

        $snapshot = $gamification->ensureFreshSnapshot($user);
        $tokenStats = $gamification->tokenUsageStats($user);

        $approved = InterviewProcess::query()
            ->where('user_id', $user->id)
            ->where('outcome', InterviewApplicationOutcome::Approved)
            ->with(['jdDocument'])
            ->orderByDesc('updated_at')
            ->limit(3)
            ->get();

        $approvedTotal = InterviewProcess::query()
            ->where('user_id', $user->id)
            ->where('outcome', InterviewApplicationOutcome::Approved)
            ->count();

        return view('dashboard.executive', [
            'bundle' => $bundle,
            'snapshot' => $snapshot,
            'tokenStats' => $tokenStats,
            'approvedTotal' => (int) $approvedTotal,
            'approvedLatest' => $approved,
        ]);
    }
}
