<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use OpenAI;

class ChatController extends Controller
{
    public function sendMessage(Request $request, $agentId)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $agent = Agent::findOrFail($agentId);
        
        try {
            // Configurar cliente OpenAI
            $apiKey = $agent->api_key ?: config('services.openai.api_key');
            $client = OpenAI::client($apiKey);
            
            // Escolher o modelo com base no tipo do agente
            $modelName = $this->getModelNameFromType($agent->model_type);
            
            // Enviar solicitação para a API OpenAI
            $response = $client->chat()->create([
                'model' => $modelName,
                'messages' => [
                    ['role' => 'system', 'content' => "Você é {$agent->name}, um assistente de IA especializado. {$agent->description}"],
                    ['role' => 'user', 'content' => $request->message]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);
            
            return response()->json([
                'response' => $response->choices[0]->message->content
            ]);
        } catch (\Exception $e) {
            // Resposta de fallback se houver erro com a API
            return response()->json([
                'response' => "Desculpe, não consegui processar sua solicitação no momento. Erro: " . $e->getMessage()
            ], 500);
        }
    }
    
    private function getModelNameFromType($type)
    {
        // Mapear os tipos de modelo para os identificadores reais da API
        $modelMap = [
            'GPT-3.5' => 'gpt-3.5-turbo',
            'GPT-4' => 'gpt-4',
            'Claude' => 'gpt-4', // Fallback para GPT-4 já que não temos acesso direto à API do Claude aqui
            'Custom' => 'gpt-3.5-turbo', // Modelo padrão para tipos personalizados
        ];
        
        return $modelMap[$type] ?? 'gpt-3.5-turbo';
    }
}