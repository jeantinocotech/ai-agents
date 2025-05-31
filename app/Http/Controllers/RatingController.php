<?php

namespace App\Http\Controllers;

use App\Models\AgentRating;
use App\Models\Agent;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RatingController extends Controller
{
    /**
     * Exibe o formulário de avaliação para o usuário após uma sessão de chat
     */
    public function showRatingForm($chatSessionId)
    {
        $chatSession = ChatSession::with('agent')->findOrFail($chatSessionId);
        
        // Verificar se o usuário é o dono desta sessão
        if ($chatSession->user_id !== Auth::id()) {
            return redirect()->route('home')->with('error', 'Você não tem permissão para avaliar esta sessão.');
        }
        
        // Verificar se já existe uma avaliação para esta sessão
        $existingRating = AgentRating::where('user_id', Auth::id())
            ->where('chat_session_id', $chatSessionId)
            ->first();
            
        return view('ratings.form', compact('chatSession', 'existingRating'));
    }
    
    /**
     * Salva a avaliação do usuário para um agente
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'chat_session_id' => 'required|exists:chat_sessions,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);
        
        $chatSession = ChatSession::findOrFail($validated['chat_session_id']);
        
        // Verificar se o usuário é o dono desta sessão
        if ($chatSession->user_id !== Auth::id()) {
            return redirect()->route('home')->with('error', 'Você não tem permissão para avaliar esta sessão.');
        }
        
        // Atualizar ou criar avaliação
        AgentRating::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'agent_id' => $validated['agent_id'],
                'chat_session_id' => $validated['chat_session_id'],
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );
        
        return redirect()->route('chat.history')->with('success', 'Obrigado pela sua avaliação!');
    }
    
    /**
     * Atualiza uma avaliação existente
     */
    public function update(Request $request, $id)
    {
        $rating = AgentRating::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);
        
        $rating->update([
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);
        
        return redirect()->route('chat.history')->with('success', 'Sua avaliação foi atualizada!');
    }
    
    /**
     * Lista todas as avaliações de um agente específico
     */
    public function agentRatings($agentId)
    {
        $agent = Agent::findOrFail($agentId);
        
        $ratings = AgentRating::where('agent_id', $agentId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        $averageRating = AgentRating::where('agent_id', $agentId)->avg('rating') ?? 0;
        $totalRatings = AgentRating::where('agent_id', $agentId)->count();
        
        $ratingDistribution = [
            5 => AgentRating::where('agent_id', $agentId)->where('rating', 5)->count(),
            4 => AgentRating::where('agent_id', $agentId)->where('rating', 4)->count(),
            3 => AgentRating::where('agent_id', $agentId)->where('rating', 3)->count(),
            2 => AgentRating::where('agent_id', $agentId)->where('rating', 2)->count(),
            1 => AgentRating::where('agent_id', $agentId)->where('rating', 1)->count(),
        ];
        
        return view('ratings.agent-ratings', compact(
            'agent', 
            'ratings', 
            'averageRating', 
            'totalRatings', 
            'ratingDistribution'
        ));
    }
    
    /**
     * Exibe avaliações que o usuário logado fez
     */
    public function myRatings()
    {
        $ratings = AgentRating::where('user_id', Auth::id())
            ->with(['agent', 'chatSession'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('ratings.my-ratings', compact('ratings'));
    }
    
    /**
     * Remove uma avaliação (apenas o próprio usuário pode remover)
     */
    public function destroy($id)
    {
        $rating = AgentRating::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $rating->delete();
        
        return redirect()->back()->with('success', 'Avaliação removida com sucesso!');
    }
    
    /**
     * API para obter estatísticas rápidas de um agente
     */
    public function getAgentRatingStats($agentId)
    {
        $stats = DB::table('agent_ratings')
            ->where('agent_id', $agentId)
            ->selectRaw('
                COUNT(*) as total_ratings,
                AVG(rating) as average_rating,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_stars,
                COUNT(CASE WHEN rating = 4 THEN 1 END) as four_stars,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as three_stars,
                COUNT(CASE WHEN rating = 2 THEN 1 END) as two_stars,
                COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
            ')
            ->first();
            
        return response()->json($stats);
    }
    
    /**
     * Solicita avaliação após finalizar sessão de chat
     */
    public function requestRating($chatSessionId)
    {
        $chatSession = ChatSession::with('agent')->findOrFail($chatSessionId);
        
        // Verificar se o usuário é o dono desta sessão
        if ($chatSession->user_id !== Auth::id()) {
            return redirect()->route('home')->with('error', 'Sessão não encontrada.');
        }
        
        // Verificar se já foi avaliada
        $existingRating = AgentRating::where('user_id', Auth::id())
            ->where('chat_session_id', $chatSessionId)
            ->exists();
            
        if ($existingRating) {
            return redirect()->route('chat.history')->with('info', 'Você já avaliou esta sessão.');
        }
        
        // Mostrar popup ou redirect para formulário de avaliação
        return redirect()->route('ratings.form', $chatSessionId)
            ->with('success', 'Sessão finalizada! Que tal avaliar sua experiência?');
    }
    
    /**
     * Modal rápido de avaliação (AJAX)
     */
    public function quickRatingModal($chatSessionId)
    {
        $chatSession = ChatSession::with('agent')->findOrFail($chatSessionId);
        
        if ($chatSession->user_id !== Auth::id()) {
            return response()->json(['error' => 'Não autorizado'], 403);
        }
        
        $existingRating = AgentRating::where('user_id', Auth::id())
            ->where('chat_session_id', $chatSessionId)
            ->first();
            
        return response()->json([
            'chatSession' => $chatSession,
            'existingRating' => $existingRating,
            'html' => view('ratings.quick-modal', compact('chatSession', 'existingRating'))->render()
        ]);
    }
    
    /**
     * Salva avaliação rápida via AJAX
     */
    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'chat_session_id' => 'required|exists:chat_sessions,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);
        
        $chatSession = ChatSession::findOrFail($validated['chat_session_id']);
        
        if ($chatSession->user_id !== Auth::id()) {
            return response()->json(['error' => 'Não autorizado'], 403);
        }
        
        $rating = AgentRating::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'agent_id' => $validated['agent_id'],
                'chat_session_id' => $validated['chat_session_id'],
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Obrigado pela sua avaliação!',
            'rating' => $rating
        ]);
    }
}