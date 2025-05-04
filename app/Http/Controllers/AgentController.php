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
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordLoader;


class AgentController extends Controller
{
    public function index()
    {
        Log::info('Acessando index do agente');

        $agents = Agent::all();
        return view('agents.index', compact('agents'));
    }

    public function show($id)
    {
        
        Log::info('Acessando agent.show ', ['agent_id' => $id]);

        $agent = Agent::findOrFail($id);
        return view('agents.show', compact('agent'));
    }

    public function chat($id)
    {
        Log::info('Acessando chat do agente', ['agent_id' => $id]);

        $agent = Agent::findOrFail($id);
        $steps = $agent->steps_order; // Supondo que há um relacionamento definido no modelo Agent
        return view('agents.chat', compact('agent','steps'));
    }


    public function getAgentInstructions($agentId)
    {
        
        Log::info('GetagendInstructions', [
            'agent_id' => $agentId,
        ]);
        
        $agent = Agent::with('steps')->findOrFail($agentId);

        return response()->json([
            'system_prompt' => $agent->system_prompt,
            'steps' => $agent->steps->map(function ($step) {
                return [
                    'order' => $step->step_order,
                    'description' => $step->name,
                    'required_inputs' => explode(',', $step->required_input),
                ];
            }),
        ]);
    }


    public function getCurrentStep(Request $request, $agentId)
    {
        
        $user = auth()->user();
    
        Log::info('GetcurrentStep', ['agent_id' => $agentId]);

        $session = ChatSession::where('user_id', $user->id)
            ->where('agent_id', $agentId)
            ->latest()
            ->first();
    
        if (!$session) {
            // Se não há sessão, estamos no primeiro passo
            $firstStep = AgentStep::where('agent_id', $agentId)
                ->orderBy('step_order')
                ->first();
                
            Log::info('Sem sessão existente, retornando primeiro passo', [
                'required_input' => $firstStep->required_input ?? null
            ]);
            
            return response()->json([
                'required_input' => $firstStep->required_input ?? null
            ]);
        }
    
        // Obtém o passo atual baseado no current_step da sessão
        $step = AgentStep::where('agent_id', $agentId)
            ->orderBy('step_order')
            ->skip($session->current_step)
            ->first();
    
        Log::info('Retornando passo atual', [
            'session_id' => $session->id,
            'current_step' => $session->current_step,
            'required_input' => $step->required_input ?? null
        ]);
    
        return response()->json([
            'required_input' => $step->required_input ?? null,
            'session_id' => $session->id,
            'current_step' => $session->current_step
        ]);
    }
    
    public function sendChat(Request $request)
    {
        
        Log::info('SendChat', [
            'message' => $request->message,
            'agent_id' => $request->agent_id,
            'session_id' => $request->session_id,
            'ai_model' => $request->ai_model
        ]);

        $request->validate([
            'message' => 'required|string',
            'agent_id' => 'required|exists:agents,id',
            'session_id' => 'nullable|exists:chat_sessions,id',
            'ai_model' => 'required|string'
        ]);

        $user = auth()->user();
        $agent = Agent::findOrFail($request->agent_id);
        $aiModel = $request->ai_model;

        Log::info('Iniciando chamada de IA', [
            'agent_id' => $agent->id,
            'message' => $request->message,
            'model' => $aiModel
        ]);

        // Recupera ou cria nova sessão
        $session = $request->session_id
            ? ChatSession::findOrFail($request->session_id)
            : ChatSession::create([
                'user_id' => $user->id,
                'agent_id' => $agent->id,
                'ai_model' => $aiModel,
                'current_step' => 0,
                'should_persist' => true,
                'is_active' => true
            ]);

        Log::info('Apos criar session', [
            'user_id' => $user->id,
            'agent_id' => $agent->id,
            'ai_model' => $aiModel,
            'current_step' => 0,
            'should_persist' => true,
            'is_active' => true
        ]);

        Log::info('Conteudo do Request no SendChat', [
            request()->all()
        ]);

        // Passo atual baseado na posição da sessão
        $currentStep = AgentStep::where('agent_id', $agent->id)
            ->orderBy('step_order')
            ->skip($session->current_step)
            ->first();

        Log::info('Passo atual do agente', [
            'step_order' => $currentStep ? $currentStep->step_order : null,
            'required_input' => $currentStep ? $currentStep->required_input : null
        ]);

         // Processamento de arquivos
         $requiredInputs = $currentStep ? explode(',', $currentStep->required_input) : [];
         $filesProcessed = [];

          // Processa cada tipo de arquivo esperado no passo atual
        foreach ($requiredInputs as $inputType) {

            $file = $request->file($inputType);

            // Salva o arquivo no storage
            $path = $file->store('uploads/chat_files', 'public');
        
            // Detecta extensão
            $extension = strtolower($file->getClientOriginalExtension());
            $extractedText = '';

            try {
                if ($extension === 'pdf') {
                    $parser = new PdfParser();
                    $pdf = $parser->parseFile($file->getRealPath());
                    $extractedText = $pdf->getText();
                } elseif (in_array($extension, ['docx', 'doc'])) {
                    $phpWord = WordLoader::load($file->getRealPath(), 'Word2007');
                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                // Se o elemento tem o método getText, extraia o texto diretamente
                                $extractedText .= $element->getText() . "\n";
                            } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                                // Se o elemento for um TextRun, itere sobre seus subelementos
                                foreach ($element->getElements() as $subElement) {
                                    if (method_exists($subElement, 'getText')) {
                                        $extractedText .= $subElement->getText() . "\n";
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $extractedText = '[Formato não suportado para leitura automática]';
                }
            } catch (\Exception $e) {
                Log::error("Erro ao extrair conteúdo do arquivo: " . $e->getMessage());
                $extractedText = '[Erro ao ler o arquivo]';
            }
            
            // Obtém informações básicas sobre o arquivo
            $fileInfo = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'path' => $path
            ];
            
             // Salva como mensagem da conversa
            ChatMessage::create([
                'chat_session_id' => $session->id,
                'role' => 'user',
                'content' => "Conteúdo do arquivo \"{$inputType}\" extraído automaticamente:\n\n" . $extractedText,
            ]);

            $filesProcessed[] = $inputType;

            Log::info("Arquivo {$inputType} processado e salvo", [
                'name' => $file->getClientOriginalName(),
                'path' => $path
            ]);

        }

        // Verifica se todos os arquivos requeridos foram processados
        $allFilesProcessed = count($filesProcessed) === count($requiredInputs);
        
        // Salva a mensagem do usuário
        if ($request->message) {
            ChatMessage::create([
                'chat_session_id' => $session->id,
                'role' => 'user',
                'content' => $request->message,
            ]);
        }
          
        Log::info('Depois de salvar message retorno');


          // Se todos os arquivos foram processados ou não haviam arquivos requeridos
          if ($allFilesProcessed || empty($requiredInputs)) {
            // Avança para o próximo passo se necessário
            if ($currentStep) {
                $session->current_step += 1;
                $session->save();
                
                Log::info('Avançou para o próximo passo', [
                    'new_step' => $session->current_step
                ]);
            }
            
            // Verifica qual é o próximo passo (se houver)
            $nextStep = AgentStep::where('agent_id', $agent->id)
                ->orderBy('step_order')
                ->skip($session->current_step)
                ->first();

            $nextRequiredInput = $nextStep ? $nextStep->required_input : null;
            
            // Prepara a chamada para a IA
            $messages = $this->prepareMessagesForAI($session);
            
            // Chamada ao modelo
            $reply = null;
            if ($aiModel === 'gpt-4') {
                $reply = $this->callOpenAI($agent, $messages);
            } elseif ($aiModel === 'claude-3') {
                $reply = $this->callAnthropic($agent, $messages);
            }

            if (!$reply) {
                return response()->json([
                    'reply' => 'Erro ao se comunicar com o agente.',
                    'model_used' => $aiModel
                ], 500);
            }

            // Salva resposta da IA
            ChatMessage::create([
                'chat_session_id' => $session->id,
                'role' => 'assistant',
                'content' => $reply,
            ]);

            return response()->json([
                'reply' => $reply,
                'session_id' => $session->id,
                'model_used' => $aiModel,
                'next_required_input' => $nextRequiredInput
            ]);
        } else {
            // Se nem todos os arquivos foram processados
            $missingInputs = array_diff($requiredInputs, $filesProcessed);
            $nextRequiredInput = reset($missingInputs);
            
            return response()->json([
                'reply' => "Por favor, envie o arquivo: " . strtoupper($nextRequiredInput),
                'session_id' => $session->id,
                'model_used' => $aiModel,
                'next_required_input' => $nextRequiredInput
            ]);
        }
    }

    private function prepareMessagesForAI($session)
    {
        $messages = [];
        
        // Obtém histórico de mensagens da sessão
        $history = ChatMessage::where('chat_session_id', $session->id)
                    ->orderBy('created_at')
                    ->get();
        
        foreach ($history as $message) {
            $messages[] = [
                'role' => $message->role,
                'content' => $message->content
            ];
        }
        
        return $messages;
    }
  
    /**
     * Faz chamada para a API da OpenAI
     */
    private function callOpenAI($agent, $messageHistory)
    {
       
        Log::info('CallOpenAI', [
            'API Key ' => trim($agent->api_key),
            'agent_id' => $agent->id,
            'OpenAI-Project' => trim($agent->project_id),
            'OpenAI-Organization' => trim($agent->organization ?? ''),
            'model' => $agent->model_type,
            'messages' => $messageHistory
        ]);
       
        // Verificar se existe uma mensagem de sistema no histórico
        $hasSystemMessage = false;
        $formattedMessages = [];
        
        foreach ($messageHistory as $msg) {
            if ($msg['role'] === 'system') {
                $hasSystemMessage = true;
            }
            $formattedMessages[] = $msg;
        }
        
        // Se não tiver mensagem de sistema, adicionar o system_prompt do agente
        if (!$hasSystemMessage && $agent->system_prompt) {
            array_unshift($formattedMessages, [
                'role' => 'system',
                'content' => $agent->system_prompt
            ]);
            
            Log::info('Adicionado system_prompt do agente', [
                $formattedMessages
            ]);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($agent->api_key),
                'OpenAI-Organization' => trim($agent->organization ?? ''),
                'OpenAI-Project' => trim($agent->project_id ?? ''),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $agent->model_type,
                'messages' => $formattedMessages,
                'temperature' => 0.7,
            ]);
        
            if (!$response->successful()) {
                Log::error('Erro OpenAI', ['response' => $response->json()]);
                return false;
            }
        
            return $response->json()['choices'][0]['message']['content'] ?? 'Sem resposta do modelo.';
        } catch (\Exception $e) {
            Log::error('Exceção OpenAI: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Faz chamada para a API da Anthropic (Claude)
     */
    private function callAnthropic($agent, $messageHistory)
    {
        
        Log::info('CallAntrropic', [
            'agent_id' => $agent->id,
            'model' => $agent->model_type,
            'messages' => $messageHistory
        ]);

        try {
             // Converte o formato de mensagens para o esperado pela API da Anthropic
             $messages = [];
             $systemPrompt = $agent->system_prompt; // Usar o system_prompt do agente


            foreach ($messageHistory as $msg) {
                if ($msg['role'] === 'system') {
                    // Se já tiver uma mensagem de sistema, vamos apenas salvar e não adicionar à lista
                    // mas damos preferência ao system_prompt do agente
                    if (!$systemPrompt) {
                        $systemPrompt = $msg['content'];
                    }
                    continue;
                }
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
            
            $response = Http::withHeaders([
                'x-api-key' => env('ANTHROPIC_API_KEY'),
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-3-7-sonnet-20250219',
                'max_tokens' => 4000,
                'messages' => $messages,
                'system' => $systemPrompt ?? 'You are a helpful assistant.'
            ]);


            if (!$response->successful()) {
                Log::error('Erro Anthropic', ['response' => $response->json()]);
                return false;
            }
            
            return $response->json()['content'][0]['text'] ?? 'Sem resposta do Claude.';
        } catch (\Exception $e) {
            Log::error('Exceção Anthropic: ' . $e->getMessage());
            return false;
        }
    }

  
}