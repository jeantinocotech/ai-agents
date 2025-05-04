<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Auth;
use App\Models\AgentStep;

class AgentController extends Controller
{
    public function index()
    {
        $agents = Agent::all();
        return view('agents.index', compact('agents'));
    }

    public function show($id)
    {
        $agent = Agent::findOrFail($id);
        return view('agents.show', compact('agent'));
    }

    public function chat($id)
    {
        $agent = Agent::findOrFail($id);
        return view('agents.chat', compact('agent'));
    }

    public function sendChat(Request $request)
    {
       
        Log::info('Iniciando chamada SendChat', [
            'message' => $request->message,
            'agent_id' => $request->agent_id,
            'session_id' => $request->session_id
        ]);
       
        $request->validate([
            'message' => 'required|string',
            'agent_id' => 'required|exists:agents,id',
            'session_id' => ['nullable', 'integer', 'exists:chat_sessions,id']
        ]);
    
        $user = Auth::user();
        $agent = Agent::findOrFail($request->agent_id);
    
        Log::info('Iniciando chamada OpenAI', [
            'agent_id' => $agent->id,
            'message' => $request->message,
            'OpenAI-Organization' => $agent->organization,
            'OpenAI-Project' => $agent->project_id,
            'model_type' => $agent->model_type,
            'api_key' => $agent->api_key
        ]);

        // Recupera ou cria sessão
        if ($request->filled('session_id')) {
            $session = ChatSession::find($request->session_id);
        } else {
            $session = ChatSession::create([
                'user_id' => $user->id,
                'agent_id' => $agent->id,
                'current_step' => 1
            ]);
    
            // Mensagem de boas-vindas automática
            ChatMessage::create([
                'chat_session_id' => $session->id,
                'role' => 'assistant',
                'content' => 'Olá! Para começarmos, por favor envie o seu currículo atualizado (CV).'
            ]);
        }
    
        // Salva a mensagem do usuário
        ChatMessage::create([
            'chat_session_id' => $session->id,
            'role' => 'user',
            'content' => $request->message,
        ]);
    
        // Verifica o passo atual
        $currentStep = AgentStep::where('agent_id', $agent->id)
                                ->where('step_order', $session->current_step)
                                ->first();
    
        if ($currentStep) {
            // Checa se a mensagem contém palavras esperadas
            $found = false;
            foreach ($currentStep->expected_keywords ?? [] as $keyword) {
                if (str_contains(strtolower($request->message), strtolower($keyword))) {
                    $found = true;
                    break;
                }
            }
    
            if ($found) {
                // Salva o input conforme o tipo esperado
                if ($currentStep->required_input == 'cv') {
                    $session->cv_text = $request->message;
                } elseif ($currentStep->required_input == 'jd') {
                    $session->job_description_text = $request->message;
                }
    
                $session->current_step += 1;
                $session->save();
    
                // Verifica se existe um próximo passo
                $nextStep = AgentStep::where('agent_id', $agent->id)
                                     ->where('step_order', $session->current_step)
                                     ->first();
    
                if ($nextStep) {
                    // Pede o próximo item
                    ChatMessage::create([
                        'chat_session_id' => $session->id,
                        'role' => 'assistant',
                        'content' => $nextStep->system_message,
                    ]);
    
                    return response()->json([
                        'reply' => $nextStep->system_message,
                        'session_id' => $session->id
                    ]);
                }
            } else {
                // Input não reconhecido para o passo atual
                $msg = "Ainda estou aguardando: " . $currentStep->name . ".\n" . $currentStep->system_message;
                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'role' => 'assistant',
                    'content' => $msg,
                ]);
    
                return response()->json([
                    'reply' => $msg,
                    'session_id' => $session->id
                ]);
            }
        }
    
        // Se todos os passos foram cumpridos, agora podemos falar com a OpenAI normalmente
        $messageHistory = collect([
            [
                'role' => 'system',
                'content' => $agent->system_prompt ?? 'You are a helpful assistant.'
            ]
        ])->merge(
            $session->messages()->orderBy('created_at')->get()->map(function ($msg) {
                return [
                    'role' => $msg->role,
                    'content' => $msg->content
                ];
            })
        )->toArray();
    
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . trim($agent->api_key),
            'OpenAI-Organization' => trim($agent->organization),
            'OpenAI-Project' => trim($agent->project_id),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $agent->model_type,
            'messages' => $messageHistory,
            'temperature' => 0.7,
        ]);
    
        if (!$response->successful()) {
            return response()->json(['reply' => 'Erro ao se comunicar com o agente.'], 500);
        }
    
        $reply = $response->json()['choices'][0]['message']['content'] ?? 'Sem resposta do modelo.';
    
        ChatMessage::create([
            'chat_session_id' => $session->id,
            'role' => 'assistant',
            'content' => $reply,
        ]);
    
        return response()->json([
            'reply' => $reply,
            'session_id' => $session->id
        ]);
    }
   
}