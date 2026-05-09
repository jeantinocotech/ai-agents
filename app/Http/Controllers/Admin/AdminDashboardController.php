<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentRating;
use App\Services\AdminOverviewMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request, AdminOverviewMetricsService $overview)
    {
        $period = $overview->resolvePeriod($request->query('period'));
        $gathered = $overview->gather($period);

        return view('admin.dashboard', array_merge(
            [
                'period' => $period,
                'periodOptions' => AdminOverviewMetricsService::PERIOD_LABELS,
            ],
            $gathered,
        ));
    }

    public function agentStats($id)
    {
        $agent = Agent::findOrFail($id);

        $totalUsers = $agent->purchases()->count();
        $totalSessions = $agent->chatSessions()->count();

        $ratings = AgentRating::where('agent_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $ratingDistribution = [
            5 => AgentRating::where('agent_id', $id)->where('rating', 5)->count(),
            4 => AgentRating::where('agent_id', $id)->where('rating', 4)->count(),
            3 => AgentRating::where('agent_id', $id)->where('rating', 3)->count(),
            2 => AgentRating::where('agent_id', $id)->where('rating', 2)->count(),
            1 => AgentRating::where('agent_id', $id)->where('rating', 1)->count(),
        ];

        $averageRating = AgentRating::where('agent_id', $id)->avg('rating') ?? 0;

        $sessionDateExpr = DB::connection()->getDriverName() === 'sqlite'
            ? 'date(created_at)'
            : 'DATE(created_at)';
        $lastWeekSessions = $agent->chatSessions()
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->selectRaw($sessionDateExpr.' as date, count(*) as total')
            ->groupByRaw($sessionDateExpr)
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        $weeklyChartData = [
            'labels' => [],
            'sessions' => [],
        ];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $weeklyChartData['labels'][] = Carbon::parse($date)->format('d/m');
            $weeklyChartData['sessions'][] = $lastWeekSessions[$date] ?? 0;
        }

        return view('admin.agents.stats', compact(
            'agent',
            'totalUsers',
            'totalSessions',
            'ratings',
            'ratingDistribution',
            'averageRating',
            'weeklyChartData'
        ));

    }
}
