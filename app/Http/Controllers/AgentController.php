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
use phpDocumentor\Reflection\PseudoTypes\False_;
use App\Models\AgentRating;

class AgentController extends Controller
{

    // Variável de instância
    private $isNewProcess;

    public function index()
    {
        Log::info('Acessando index do agente');

        $user = auth()->user();
        $purchasedAgentIds = [];
       
    
        if ($user) {
            $purchasedAgentIds = $user->purchases()
            ->where('active', true)
            ->pluck('agent_id')->toArray(); }
    
            $agents = Agent::withAvg('ratings', 'rating')
            ->where('is_active', true)
            ->get();

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
                 ->where('active', true)
                 ->first()
        : null;

         // Buscar avaliações
        $ratings = AgentRating::where('agent_id', $id)
        ->with('user')
        ->orderBy('created_at', 'desc')
        ->paginate(5);

        // Estatísticas das avaliações
        $totalRatings = AgentRating::where('agent_id', $id)->count();
        $averageRating = AgentRating::where('agent_id', $id)->avg('rating') ?? 0;

        // Distribuição de avaliações
        $ratingDistribution = [
            5 => AgentRating::where('agent_id', $id)->where('rating', 5)->count(),
            4 => AgentRating::where('agent_id', $id)->where('rating', 4)->count(),
            3 => AgentRating::where('agent_id', $id)->where('rating', 3)->count(),
            2 => AgentRating::where('agent_id', $id)->where('rating', 2)->count(),
            1 => AgentRating::where('agent_id', $id)->where('rating', 1)->count(),
        ];

    return view('agents.show', compact(
            'agent', 
            'ratings', 
            'totalRatings', 
            'averageRating', 
            'ratingDistribution',
            'purchase'
       ));
        
    }

    public function chat($id)
    {

        $agent = Agent::with('steps')->findOrFail($id);
        $steps = $agent->steps()->orderBy('step_order')->get();

        $session = ChatSession::where('user_id', auth()->id())
            ->where('agent_id', $agent->id)
            ->orderBy('created_at', 'desc') // Ordena pela data de criação (mais recente primeiro)
            ->first(); // Obtém apenas a última sessão

        // Check if session exists, if not create a new one
        if (!$session) {
            $session = ChatSession::create([
                'user_id' => auth()->id(),
                'agent_id' => $agent->id,
                'ai_model' => $agent->model_type,
                'current_step' => 1,
                'should_persist' => true,
                'is_active' => true
            ]);
            
            $session->already_rated = false; // New session is not rated yet
        } else {
            $session->already_rated = AgentRating::where('user_id', auth()->id())
                ->where('chat_session_id', $session->id)
                ->exists();
        }

        Log::info('Acessando chat do agente', ['agent' => $agent, 'steps' => $steps]);

       // Marca todas as sessões anteriores como inativas
       if (auth()->check()) {
        ChatSession::where('user_id', auth()->id())
            ->where('agent_id', $agent->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
        }

        Log::info('Preparando nova sessão de chat', ['agent_id' => $id]);

        return view('agents.chat', compact('agent','steps','session'));
    }

    public function finalizeSession($agentId)
    {
        $agent = Agent::with('steps')->findOrFail($agentId);
        $steps = $agent->steps()->orderBy('step_order')->get();

        Log::info('Finalizando a sessão do agente', ['agent' => $agent, 'steps' => $steps]);

       // Marca todas as sessões anteriores como inativas
       if (auth()->check()) {
        ChatSession::where('user_id', auth()->id())
            ->where('agent_id', $agent->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
        }

        Log::info('Preparando nova sessão de chat', ['agent_id' => $agentId]);

        return response()->json([
            'agent' => $agent, 
            'steps' => $steps,
        ]);
    }

    
    public function getAgentInstructions($agentId)
    {
        
        Log::info('GetagendInstructions', [
            'agent_id' => $agentId,
        ]);
        
        $agent = Agent::with('steps')->findOrFail($agentId);

        return response()->json([
            'agent_description' => $agent->description,
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
        
        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
        
        $agent = Agent::with('steps')->findOrFail($agentId);

        Log::info('GetCurrentStep', ['agent_id' => $agentId, 'user_id' => $user->id]);
        
        // Verifica se já existe uma sessão ativa
        $session = ChatSession::where('user_id', $user->id)
            ->where('agent_id', $agentId)
            ->where('is_active', true)
            ->latest()
            ->first();
            
        // Se não existir, cria uma nova sessão
        if (!$session) {

            //Verifica se o agente possui passos configurados
            $firstStep = AgentStep::where('agent_id', $agentId)->orderBy('step_order')->first(); 
                
            if (!$firstStep) {
                
                $session = ChatSession::create([
                    'user_id' => $user->id,
                    'agent_id' => $agentId,
                    'ai_model' =>$agent->model_type,
                    'current_step' => 0,
                    'should_persist' => true,
                    'is_active' => true
                ]);

                Log::info('Nova sessão criada sem passos', ['session_id' => $session->id]);

                return response()->json([
                    'session_id' => $session->id,
                    'current_step' => 0
                 ]);

            }
            else {
            
                    $session = ChatSession::create([
                        'user_id' => $user->id,
                        'agent_id' => $agentId,
                        'ai_model' =>$firstStep->agent->model_type,
                        'current_step' => 1,
                        'should_persist' => true,
                        'is_active' => true,
                    ]);
                    
                    Log::info('Nova sessão criada com passos', ['session_id' => $session->id]);

                    $session->already_rated = AgentRating::where('user_id', Auth::id())
                    ->where('chat_session_id', $session->id)
                    ->exists();
                    
                    return response()->json([
                        'session_id' => $session->id,
                        'is_active' => $session->is_active,
                        'current_step' => 1,
                        'required_input' => $firstStep->required_input ?? null,
                        'upload_file' => $firstStep->upload_file    
                    ]);
                }
         
        }
               
        If ($session->current_step == 0) {
            // Se o current_step for 0, significa que a sessão está sendo criada
            // e o agente ainda não possui passos configurados

            Log::info('Nao existem passos configurados ou Todos passos foram executados');

            return response()->json([
                'session_id' => $session->id,
                'current_step' => $session->current_step,
                'required_input' =>  null,
                'upload_file' => null
            ]);
        
        } 
        
        Log::info('Session exist - GetCurrentStep', ['current_step' => $session->current_step ]);

        
        // Obtém o passo atual baseado no current_step da sessão
        $currentStep = AgentStep::with ('agent')
            ->where('agent_id', $agentId)
            ->where('step_order', $session->current_step)
            ->first();
            
        if (!$currentStep) {
            // Se não encontrou o passo atual, tenta encontrar o próximo disponível
            $currentStep = AgentStep::where('agent_id', $agentId)
                ->where('step_order', '>', $session->current_step)
                ->orderBy('step_order')
                ->first();
                
            if ($currentStep) {
                $session->current_step = $currentStep->step_order;
                $session->save();
            } else {
                    Log::info('Nao existem mais passos - ultimo passo foi: ' . $session->current_step);
                    $session->current_step = 0;
                    $session->save();
                    Log::info('current_step =' . $session->current_step);
            }

        } else {
            // Se encontrou o passo atual, não faz nada
            Log::info('Passo atual encontrado', [
                'session_id' => $session->id,
                'current_step' => $session->current_step,
                'required_input' => $currentStep->required_input ?? null,
                'upload_file' => $currentStep->upload_file ?? null
            ]);
        }
        
        return response()->json([
            'session_id' => $session->id,
            'current_step' => $session->current_step,
            'required_input' => $currentStep->required_input ?? null,
            'upload_file' => $currentStep->upload_file ?? null
        ]);
        
    }
    

    public function getNextStep($agentId, $currentStep)
    {
        Log::info('$currentStep antes', [$currentStep]);

        if ($currentStep == 0) {
            Log::info('$currentStep depois', [$currentStep]);
            return (object) ['current_step' => 0,  $require_input = 'continue'];
        }

        $user = auth()->user();

        $require_input = null;
        
        if (!$user) {
            return (object) ['current_step' => 0,  $require_input = 'continue'];
        }
        
        Log::info('GetNextStep', ['agent_id' => $agentId, 'user_id' => $user->id]);
        
        // Obtém o passo atual baseado no current_step da sessão
        $NextStep = AgentStep::with('agent')
                ->where('agent_id', $agentId)
                ->where('step_order', '>', $currentStep) // Busca o próximo passo
                ->orderBy('step_order', 'asc') // Ordena para garantir que o próximo seja o menor valor maior que $currentStep
                ->first();
        
        if (!$NextStep) {
            return (object) ['current_step' => 0, 'required_input' => 'continue' , 'keywords' => 'finalizar'];
        }
        
        Log::info('Retornando proximo passo', [$NextStep->step_order]);

        If ($NextStep->required_input) {
            $require_input = $NextStep->required_input;
        } else {
            $require_input = 'continue';
        }
        Log::info('Require input', [$NextStep->step_order, $require_input]);

        return (object) [
            'current_step' => $NextStep->step_order,
            'required_input' => $require_input,
            'keywords' => $NextStep->expected_keywords
        ];
    
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
            $aiModel = $agent->model_type;

            Log::info('Ai Model', ['ai_model' => $aiModel]);

           // Recupera ou cria nova sessão
           $session = null;
           if ($request->filled('session_id')) {
               $session = ChatSession::where('id', $request->session_id)
                   ->where('is_active', true)
                   ->first();
                   
               Log::info('Verificando sessão existente', [
                   'session_id' => $request->session_id,
                   'encontrada' => $session ? 'sim' : 'não'
               ]);
           }
            // Se não encontrou sessão válida, cria uma nova
            if (!$session) {
                $session = ChatSession::create([
                    'user_id' => $user->id,
                    'agent_id' => $agent->id,
                    'ai_model' => $aiModel,
                    'current_step' => 1,
                    'should_persist' => true,
                    'is_active' => true
                ]);
                Log::info('Nova sessão criada SendChat', ['session_id' => $session->id]);
            }

            //limpar caracteres
            $sanitizedMessage = filter_var($request->message, FILTER_SANITIZE_SPECIAL_CHARS);
            $sanitizedMessage = addslashes($sanitizedMessage);

            //Grava a mensagem do usuário
            $message = ChatMessage::create([
                'chat_session_id' => $session->id,
                'role' => 'user',
                'content' => $sanitizedMessage
            ]);     
            
            // Prepara as mensagens para o modelo 
            if ($request->assistand_id) {
                $messages = $sanitizedMessage;
            } else {
                $messages = $this->prepareMessagesForAI($session);
            }
                
            Log::info('Mensagem preparada para envio', ['ai_model' => $aiModel, 'messages' => $messages]);
          
            // Chamada ao modelo
            $reply = null;
            set_time_limit(120);

            
            if (strtolower($aiModel) === 'gpt-4') {
                if ($agent->assistant_id) {
                    Log::info('Call AI assistant');
                    $reply = $this->callOpenAIUsingAssistant($agent, $session, $request->message);
                } else {
                    Log::info('Call OpenAI direct');
                    $reply = $this->callOpenAI($agent, $messages);
                }
            } elseif (strtolower($aiModel) === 'claude-3') {
                Log::info('Call Claude direct');
                $reply = $this->callAnthropic($agent, $messages);
            }

            if (!$reply) {
                return response()->json([
                    'reply' => 'Erro ao se comunicar com o agente.',
                    'model_used' => $aiModel
                ], 500);
            }

            Log::info('Conteudo da resposta que vai para o ChatMessage', [
                'chat_session_id' => $session->id,
                'role' => 'assistant',
                'content' => $reply
            ]);

            // Salva resposta da IA
            ChatMessage::create([
                'chat_session_id' => $session->id,
                'role' => 'assistant',
                'content' => $reply,
            ]);

            $currentStep = $this->getNextStep($request->agent_id, $session->current_step);
   
            if ( $currentStep->current_step > 0 ) {
                Log::info("Next step:", [
                    'Step = ' => $currentStep->current_step, 'keywords' => $currentStep->keywords
                ]);

                 // Obter as keywords do próximo passo
                $keywords =  $currentStep->keywords;

                // Verificar se a resposta da API contém alguma das keywords
                $containsKeyword = false;
                foreach ($keywords as $keyword) {
                    if (str_contains(strtolower($reply), strtolower(trim($keyword)))) { // Converte ambos para minúsculas
                        $containsKeyword = true;
                        break;
                    }
                }

                if ($containsKeyword) {
                    $session->current_step = $currentStep->current_step;  
                    $session->save();
                } else {
                    Log::info('Resposta não contém keywords. Mantendo no passo atual.');
                }

            } else {

                $session->current_step = 0;  
                $session->save();

                Log::info("Nao encontrou passo para o agente", [
                    'Step = ' => $currentStep->current_step 
                ]);

                return response()->json([
                    'reply' => $reply,
                    'session_id' => $request->session_id,
                    'model_used' => $aiModel,
                    'current_step' => 0,
                    'next_required_input' => 'continue'
                ]);
            }

            Log::info("update Chatsession with next step", [
                'Step = ' => $session->current_step
            ]);
          
            ChatSession::where('id', $session->id) // Filtra pela sessão atual
                ->update([
                    'current_step' => $session->current_step
                ]);
                            

            if (!$currentStep->required_input) {
                return response()->json([
                    'reply' => $reply,
                    'session_id' => $session->id,
                    'model_used' => $aiModel,
                    'current_step' => $session->current_step,
                ]);
            } else {
                return response()->json([
                    'reply' => $reply,
                    'session_id' => $session->id,
                    'model_used' => $aiModel,
                    'current_step' => $session->current_step,
                    'next_required_input' => $currentStep->required_input
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


    public function sendFile(Request $request)
    {
        try {
            Log::info('SendFile', [
                'message' => $request->message,
                'agent_id' => $request->agent_id,
                'session_id' => $request->session_id,
                'ai_model' => $request->ai_model
            ]);

            $request->validate([
                'message' => 'required|string',
                'agent_id' => 'required|exists:agents,id',
                'session_id' => 'nullable|exists:chat_sessions,id',
                'ai_model' => 'required|string',
                'inputType' => 'required|string'
            ]);

            $user = auth()->user();
            $agent = Agent::findOrFail($request->agent_id);
            $session = ChatSession::findOrFail($request->session_id);
            $aiModel = $request->ai_model;
            $inputType = $request->input('inputType');

            if ($request->hasFile($inputType)) {


                $file = $request->file($inputType);
                
                Log::info('Arquivo encontrado indo para o processamento', [
                    'File' => $file
                ]);

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
                    'chat_session_id' => $request->session_id,
                    'role' => 'user',
                    'content' => "Conteúdo do arquivo \"{$inputType}\" extraído automaticamente:\n\n" . $extractedText,
                ]);

                $filesProcessed[] = $inputType;

                Log::info("Arquivo {$inputType} processado e salvo", [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path
                ]);

                
                $currentStep = $this->getNextStep($request->agent_id, $session->current_step);
                
                if ($currentStep) {
                    $session->current_step = $currentStep['current_step'];
                    $session->save();
                } else {
                    return response()->json([
                        'session_id' => $request->session_id,
                        'current_step' => null,
                        'next_required_input' => null
                    ]);
                }

                Log::info("update Chatsession with next step", [
                    'Step = ' => $session->current_step
                ]);

                // Atualiza a sessão com o novo passo
                ChatSession::where('id', $session->id) // Filtra pela sessão atual
                    ->update([
                        'current_step' => $session->current_step
                    ]);



                return response()->json([
                    'session_id' => $request->session_id,
                    'current_step' => $currentStep['current_step'],
                    'next_required_input' => $currentStep['required_input']
                ]);

            } else {
                Log::info("Arquivo {$inputType} não enviado neste request.");
            }

        } catch (\Throwable $e) {
            Log::error('Erro inesperado em SendFile(): ' . $e->getMessage(), [
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
    
        // Histórico da sessão
       
        if ( $agent->assistant_id) {
            $history = ChatMessage::where('chat_session_id', $session->id)
                    ->orderBy('created_at')
                    ->take(1)
                    ->get()
                    ->reverse();
        } else {

            // Adiciona o system_prompt do agente como primeira mensagem
            if ($agent && $agent->system_prompt) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $agent->system_prompt
                ];
            }

            $history = ChatMessage::where('chat_session_id', $session->id)
                    ->orderBy('created_at')
                    ->take(4)
                    ->get()
                    ->reverse();
        }

        foreach ($history as $message) {
            $messages[] = [
                'role' => $message->role,
                'content' => $message->content
            ];
        }
    
        return $messages;
    }
    
    public function startNewSession(Request $request)
    {
        $user = auth()->user();
        $agentId = $request->input('agent_id');
        
        if (!$user || !$agentId) {
            return response()->json([
                'error' => 'Usuário não autenticado ou agente não especificado'
            ], 400);
        }
        
        // Desativa todas as sessões ativas para este agente
        ChatSession::where('user_id', $user->id)
            ->where('agent_id', $agentId)
            ->where('is_active', true)
            ->update(['is_active' => false]);
            
        // Cria uma nova sessão
        $session = ChatSession::create([
            'user_id' => $user->id,
            'agent_id' => $agentId,
            'ai_model' => $request->input('ai_model', 'gpt-4'),
            'current_step' => 1,
            'should_persist' => true,
            'is_active' => true
        ]);
        
        // Obtém o primeiro passo
        $firstStep = AgentStep::where('agent_id', $agentId)
            ->orderBy('step_order')
            ->first();
            
        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'current_step' => 1,
            'required_input' => $firstStep ? $firstStep->required_input : null
        ]);
    }
  
    /**
     * Faz chamada para a API da OpenAI
     */
    private function callOpenAI($agent, $messageHistory)
    {
       
        Log::info('CallOpenAI', [
            'messages' => $messageHistory
        ]);


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
    
        Log::info('Antes de enviar Thread, ', [
            'thread_id' => $session->thread_id
        ]);

        set_time_limit(120);

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
        
        $startTime = time();
        $maxWait = 120;
        $tentativas = 0;
    
        // Espera o run finalizar
        do {
            usleep(100000);

            $runCheck = $http->get("https://api.openai.com/v1/threads/{$session->thread_id}/runs/{$run['id']}");
            $status = $runCheck['status'] ?? null;
            Log::info('Verificando status do run', ['status' => $status, 'elapsed_time' => time() - $startTime]);

            if ((time() - $startTime) > $maxWait) {
                Log::warning("Timeout na espera pelo run OpenAI");
                $http->post("https://api.openai.com/v1/threads/{$session->thread_id}/runs/{$run['id']}/abort");
                return null;
            }

            if ($status == 'failed' and $tentativas < 3) {
                Log::error('Run finalizou com status inesperado: ' . $status . '#' . $tentativas);
                $run = $http->post("https://api.openai.com/v1/threads/{$session->thread_id}/runs", [
                    'assistant_id' => $agent->assistant_id,
                ]);
                
                usleep(100000);
                $runCheck = $http->get("https://api.openai.com/v1/threads/{$session->thread_id}/runs/{$run['id']}");
                $status = $runCheck['status'] ?? null;
                $tentativas++;
            }

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
        Log::info('Recuperou Thread', [
            $messagesResponse->json()
        ]);

        // Pega a última resposta do assistant
        foreach (array_reverse($messages->all()) as $msg) {
            Log::info('Entrou no Foreach do array reverse');
            if ($msg['role'] === 'assistant') {
                Log::info('Resposta do assistant encontrada', [
                    'content' => $msg['content'][0]['text']['value'] ?? ''
                ]);

                Log::info('Executou sem erros');
                // Retorna o conteúdo da resposta
                return $msg['content'][0]['text']['value'] ?? '';
            }
        }

        return null;
    }
    
}