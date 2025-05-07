<?php

namespace App\Http\Controllers;

use App\Models\AgentRating;
use App\Models\Agent;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
