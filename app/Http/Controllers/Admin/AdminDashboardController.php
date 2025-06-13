<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\User;
use App\Models\ChatSession;
use App\Models\AgentRating;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\HotmartService;


class AdminDashboardController extends Controller
{
    public function index()
    {
        // Estatísticas gerais
        $totalAgents = Agent::count();
        $totalUsers = User::count();
        $totalSessions = ChatSession::count();

        Log::info('Statistica', [
            'totalAgents' => $totalAgents,
            'totalUsers' => $totalUsers,
            'totalSessions' => $totalSessions
        ]);
        
        // Estatísticas detalhadas por agente
        $agentStats = Agent::select('agents.*')
            ->withCount('purchases as users_count')
            ->withCount('chatSessions as sessions_count')
            ->leftJoin('agent_ratings', 'agents.id', '=', 'agent_ratings.agent_id')
            ->groupBy('agents.id')
            ->selectRaw('COALESCE(AVG(agent_ratings.rating), 0) as average_rating')
            ->orderBy('sessions_count', 'desc')
            ->get();
        
            Log::info('Statistica', [
                'totalAgents' => $agentStats,
            ]);


        // Últimas avaliações com comentários
        $latestRatings = AgentRating::with(['user', 'agent'])
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

            Log::info('Rating', [
                'totalAgents' => $latestRatings,
            ]);
            
        // Dados para o gráfico de uso nos últimos 30 dias
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $sessionsPerDay = ChatSession::where('created_at', '>=', $thirtyDaysAgo)
            ->groupBy('date')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->get()
            ->pluck('total', 'date')
            ->toArray();

            Log::info('Sessions per day', [
                'totalAgents' => $sessionsPerDay,
            ]);
            
        // Preparar dados para Chart.js
        $chartData = [
            'labels' => [],
            'sessions' => []
        ];
        
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartData['labels'][] = Carbon::parse($date)->format('d/m');
            $chartData['sessions'][] = $sessionsPerDay[$date] ?? 0;
        }
        
        // Inverter arrays para mostrar ordem cronológica correta
        $chartData['labels'] = array_reverse($chartData['labels']);
        $chartData['sessions'] = array_reverse($chartData['sessions']);
        
        return view('admin.dashboard', compact(
            'totalAgents', 
            'totalUsers', 
            'totalSessions', 
            'agentStats', 
            'latestRatings', 
            'chartData'
        ));
    }
    
    public function agentStats($id)
    {
        $agent = Agent::findOrFail($id);
        
        // Estatísticas de uso
        $totalUsers = $agent->purchases()->count();
        $totalSessions = $agent->chatSessions()->count();
        
        // Obter avaliações
        $ratings = AgentRating::where('agent_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        // Distribuição de avaliações
        $ratingDistribution = [
            5 => AgentRating::where('agent_id', $id)->where('rating', 5)->count(),
            4 => AgentRating::where('agent_id', $id)->where('rating', 4)->count(),
            3 => AgentRating::where('agent_id', $id)->where('rating', 3)->count(),
            2 => AgentRating::where('agent_id', $id)->where('rating', 2)->count(),
            1 => AgentRating::where('agent_id', $id)->where('rating', 1)->count(),
        ];
        
        // Média de avaliação
        $averageRating = AgentRating::where('agent_id', $id)->avg('rating') ?? 0;
        
        // Dados para gráfico de uso semanal
        $lastWeekSessions = $agent->chatSessions()
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->get()
            ->pluck('total', 'date')
            ->toArray();
            
        $weeklyChartData = [
            'labels' => [],
            'sessions' => []
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

    public function updateAllHotmartPrices(HotmartService $hotmartService)
    {
        $agents = Agent::whereNotNull('hotmart_product_id')->get();
        $updated = 0;

        Log::info('Preco atual - antes do For');
    
        foreach ($agents as $agent) {

            Log::info('antes da API Hotmart', [
                'agent' => $agent->name, 'product_id' => $agent->hotmart_product_id, 'price' => $agent->price]);

            $price = $hotmartService->getProductPrice($agent->hotmart_product_id);

            Log::info('depois da API Hotmart', [
                'Hotmart price' => $price,
                'Agent price' => $agent->price
            ]);

            if ($price) {
                Log::info('Novo price', [
                    'price atual' => $agent->price, 'novo preco' => $price]);
                $agent->price = $price;
                $agent->save();
                $updated++;
            }
        }
    
        return redirect()->back()->with('success', "{$updated} preços atualizados com sucesso.");
    }

}