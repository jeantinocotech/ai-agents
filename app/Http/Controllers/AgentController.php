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
use App\Models\Purchase;


class AgentController extends Controller
{
    public function index()
    {
        Log::info('Acessando index do agente');

        $user = auth()->user();
        $purchasedAgentIds = [];
    
        if ($user) {
            $purchasedAgentIds = $user->purchases()->pluck('agent_id')->toArray(); }
    
        $agents = Agent::where('is_active', true)->get();

        Log::info('Purchased Agent IDs: ' , [$purchasedAgentIds], [$agents]);
    
        return view('agents.index', compact('agents', 'purchasedAgentIds'));
    }

    public function show($id)
    {
        
        Log::info('Acessando agent.show ', ['agent_id' => $id]);

        $agent = Agent::findOrFail($id);
        $user = auth()->user();

        $purchase = $user 
        ? Purchase::where('user_id', $user->id)
                 ->where('agent_id', $agent->id)
                 ->first()
        : null;

        return view('agents.show', compact('agent', 'purchase'));
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
        //$step = AgentStep::where('agent_id', $agentId)
        //    ->orderBy('step_order')
        //    ->skip($session->current_step)
        //    ->first();

        $step = AgentStep::where('agent_id', $agentId)
            ->where ('step_order', $session->current_step)
            ->orderBy('step_order')
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
        
        
        try {
        
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

            Log::info('Required inputs - antes de processar os arquivos', [$requiredInputs]);

            // Processa cada tipo de arquivo esperado no passo atual
            foreach ($requiredInputs as $inputType) {

                Log::info('entrou no loop arquivos, mesmo sem upload', [
                    'inputType' => $inputType
                ]);

                if ($request->hasFile($inputType)) {
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
                            
                                    if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                                      
                                        foreach ($element->getElements() as $subElement) {
                                            if (method_exists($subElement, 'getText')) {
                                                
                                                $text = $subElement->getText();
                                                if (is_string($text)) {
                                               
                                                    $extractedText .= $text . "\n";
                                                } else {
                                                    Log::warning('getText() retornou tipo inesperado: ' . gettype($text) . ' em ' . get_class($subElement));
                                                }
                                         
                                            }
                                        }
                                    } elseif (method_exists($element, 'getText') && !($element instanceof \PhpOffice\PhpWord\Element\TextRun)) {

                                       
                                        $text = $element->getText();
                                        if (is_string($text)) {
                                           
                                            $extractedText .= $text . "\n";
                                        } else {
                                            Log::warning('getText() retornou tipo inesperado: ' . gettype($text) . ' em ' . get_class($subElement));
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
                    
                    Log::info("Arquivo {$inputType} processado", [
                        'name' => $extractedText,
                        'path' => $path
                    ]);
                    
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

                } else {
                    Log::info("Arquivo {$inputType} não enviado neste request.");
                }

            }

            // Verifica se todos os arquivos requeridos foram processados
            //$allFilesProcessed = count($filesProcessed) === count($requiredInputs);
            $missingInputs = array_diff($requiredInputs, $filesProcessed);

            Log::info('Antes de salvar message retorno', [$request->message]);
            
            if (count($missingInputs) === 0 && $request->filled('message')) {
                // Mensagem real do usuário, após todos os arquivos
                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'role' => 'user',
                    'content' => $request->message,
                ]);
            } elseif ($request->filled('message')) {
                Log::info('Mensagem ignorada pois ainda há arquivos pendentes.', [
                    'message' => $request->message,
                    'missing_inputs' => $missingInputs
                ]);
            }
            
            Log::info('Depois de salvar message retorno');

            if (count($missingInputs) > 0) {
                // Ainda faltam arquivos do passo atual
                $stepDescription = $currentStep->description ?? 'Envie os arquivos solicitados para continuar.';
            
                return response()->json([
                    'reply' => 'Aguardando os arquivos: ' . implode(', ', $missingInputs),
                    'next_required_input' => $missingInputs[0],
                    'step_description' => $stepDescription,
                    'session_id' => $session->id,
                    'model_used' => $aiModel
                ]);
            } else {
                // Todos os arquivos do passo atual foram recebidos → Avança
                $session->current_step += 1;
                $session->save();
            
                $nextStep = AgentStep::where('agent_id', $agent->id)
                    ->orderBy('step_order')
                    ->skip($session->current_step)
                    ->first();
            
                if ($nextStep) {
                    return response()->json([
                        'reply' => $nextStep->description ?? 'Por favor, envie os dados solicitados no próximo passo.',
                        'next_required_input' => $nextStep->required_input,
                        'step_description' => $nextStep->description,
                        'session_id' => $session->id,
                        'model_used' => $aiModel
                    ]);
                } else {
                    return response()->json([
                        'reply' => 'Todos os passos foram concluídos. Agora envie uma mensagem para iniciar a análise.',
                        'session_id' => $session->id,
                        'model_used' => $aiModel
                    ]);
                }
            }

            // Se todos os arquivos foram processados ou não haviam arquivos requeridos
            if (count($missingInputs) === 0 && $request->filled('message')) {
                
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
                    if ($agent->assistant_id) {
                        Log::info('Call AI assistant');
                        $reply = $this->callOpenAIUsingAssistant($agent, $session, $request->message);
                    } else {
                        $reply = $this->callOpenAI($agent, $messages);
                    }
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

                if ($currentStep) {
                    $session->current_step += 1;
                    $session->save();
                    
                    Log::info('Avançou para o próximo passo', [
                        'new_step' => $session->current_step
                    ]);
                }

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

        } catch (\Throwable $e) {
            Log::error('Erro inesperado em sendChat(): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'reply' => 'Erro interno ao processar sua mensagem.',
                'error' => $e->getMessage()
            ], 500);
        }    
    }

    private function prepareMessagesForAI($session)
    {
        $messages = [];
    
        $agent = Agent::find($session->agent_id);
    
        // Adiciona o system_prompt do agente como primeira mensagem
        if ($agent && $agent->system_prompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $agent->system_prompt
            ];
        }
    
        // Histórico da sessão
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

        try {

            $messages = [];
            $systemPrompt = $agent->system_prompt ?? 'Você é um assistente útil.';

            // Sempre adiciona o system prompt no início
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];

            // Adiciona as mensagens reais do histórico (sem duplicar role=system)
            foreach ($messageHistory as $msg) {
                if ($msg['role'] !== 'system') {
                    $messages[] = [
                        'role' => $msg['role'],
                        'content' => $msg['content']
                    ];
                }
            }

            Log::info('System prompt enviado para IA', ['system' => $systemPrompt]);
            Log::info('CallOpenAI Message Completa', [$messages]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($agent->api_key),
                'OpenAI-Organization' => trim($agent->organization ?? ''),
                'OpenAI-Project' => trim($agent->project_id ?? ''),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $agent->model_type,
                'messages' => $messages,
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

    private function callOpenAIUsingAssistant($agent, $session, $userMessage)
    {
        
        Log::info('AI assistant');

         // Monta headers obrigatórios
        $headers = [
            'OpenAI-Beta' => 'assistants=v2',
        ];

        if ($agent->project_id) {
            $headers['OpenAI-Project'] = $agent->project_id;
        }

        $http = Http::withToken($agent->api_key)->withHeaders($headers);
        
        // Cria thread se necessário
        if (!$session->thread_id) {
            Log::info('Criando nova thread para a sessão ' . $session->id);
        
            $threadResponse = $http->post('https://api.openai.com/v1/threads');
        
            if (!$threadResponse->successful()) {
                Log::error('Erro ao criar thread OpenAI', [
                    'status' => $threadResponse->status(),
                    'body' => $threadResponse->body(),
                ]);
                return null;
            }
        
            $session->thread_id = $threadResponse['id'];
            $session->save();
        }
    
        Log::info('Antes de enviar Thread');

        // Envia mensagem do usuário
        $http->post("https://api.openai.com/v1/threads/{$session->thread_id}/messages", [
            'role' => 'user',
            'content' => $userMessage,
        ]);
            
        Log::info('Depois de enviar Thread');

        // Cria o run
        $run = $http->post("https://api.openai.com/v1/threads/{$session->thread_id}/runs", [
            'assistant_id' => $agent->assistant_id,
        ]);
    
        Log::info('Passou RUN');
        
        if (!$run->successful()) {
        Log::error('Erro ao criar run OpenAI', [
            'status' => $run->status(),
            'body' => $run->body(),
        ]);
        return null;
    }
    
        // Espera o run finalizar
        do {
            Log::info('Entrou no loop de espera do run');
            sleep(1);
            $runCheck = $http->get("https://api.openai.com/v1/threads/{$session->thread_id}/runs/{$run['id']}");
            $status = $runCheck['status'] ?? null;
        } while (in_array($status, ['queued', 'in_progress']));
    
        if ($status !== 'completed') {
            Log::error('Run finalizou com status inesperado: ' . $status);
            return null;
        }
        
        Log::info('Saiu no loop de espera do run');
        // Recupera mensagens da thread
        $messagesResponse = $http->get("https://api.openai.com/v1/threads/{$session->thread_id}/messages");

        if (!$messagesResponse->successful()) {
            Log::error('Erro ao recuperar mensagens da thread', [
                'status' => $messagesResponse->status(),
                'body' => $messagesResponse->body(),
            ]);
            return null;
        }
       
  
        $messages = collect($messagesResponse['data'])->sortBy('created_at')->values();
        Log::info('Recuperou Thread');

        // Pega a última resposta do assistant
        foreach (array_reverse($messages->all()) as $msg) {
            Log::info('Entrou no Foreach do array reverse');
            if ($msg['role'] === 'assistant') {
                Log::info('Resposta do assistant encontrada');
                return $msg['content'][0]['text']['value'] ?? '';
            }
        }
        
        Log::info('Executou sem erros');

        return null;
    }
    
}