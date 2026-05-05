<x-app-layout>
    <div @class(['py-4 sm:py-6' => $compactTrailChatUi ?? false, 'py-12' => ! ($compactTrailChatUi ?? false)])>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div @class(['p-4 sm:p-5 text-gray-900' => $compactTrailChatUi ?? false, 'p-6 text-gray-900' => ! ($compactTrailChatUi ?? false)])>
                    @if (session('status'))
                        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                            {{ session('status') }}
                        </div>
                    @endif
                    @if ($compactTrailChatUi ?? false)
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b border-slate-100 pb-2">
                            <h2 class="text-base font-semibold tracking-tight text-slate-900 sm:text-lg">{{ $compactTrailChatTitle ?? 'Chat' }}</h2>
                            @if ($agent->isChatKitWorkflow())
                                <p class="text-xs text-slate-600">
                                    Consumidos na sessão:
                                    <strong id="chatkit-session-tokens-used" class="tabular-nums font-semibold text-amber-700">0</strong>
                                </p>
                            @endif
                        </div>
                        @if (! empty($compactTrailStep))
                            @include('agents.partials.compact-trail-graca-frame', [
                                'gracaStep' => $compactTrailStep,
                                'gracaSlot' => match ($compactTrailStep->slug) {
                                    'ats' => \App\Support\CareerTrailGracaSlots::ATS_CHAT_PAGE_INTRO,
                                    'cover-letter' => \App\Support\CareerTrailGracaSlots::COVER_LETTER_CHAT_PAGE_INTRO,
                                    'interviews' => \App\Support\CareerTrailGracaSlots::INTERVIEWS_CHAT_PAGE_INTRO,
                                    'cv' => \App\Support\CareerTrailGracaSlots::CV_ASSISTANT_CHAT_PAGE_INTRO,
                                    default => \App\Support\CareerTrailGracaSlots::ATS_CHAT_PAGE_INTRO,
                                },
                            ])
                        @endif
                    @else
                    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between">
                        <div class="flex min-w-0 flex-wrap items-center gap-3">
                            <a href="{{ route('career-trail.index') }}" class="shrink-0 text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                &larr; Voltar à trilha
                            </a>
                            @if (! empty($motivationLettersIndexUrl))
                                <span class="text-slate-300" aria-hidden="true">·</span>
                                <a href="{{ $motivationLettersIndexUrl }}" class="text-sm font-medium text-violet-700 hover:text-violet-900 hover:underline">
                                    Cartas guardadas (biblioteca)
                                </a>
                            @endif
                            @if (! empty($interviewPreparationsIndexUrl))
                                <span class="text-slate-300" aria-hidden="true">·</span>
                                <a href="{{ $interviewPreparationsIndexUrl }}" class="text-sm font-medium text-violet-700 hover:text-violet-900 hover:underline">
                                    Entrevistas registradas (dados)
                                </a>
                            @endif
                            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Chat com {{ $agent->name }}</h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            @if($agent->isChatKitWorkflow())
                                <div id="token-balance-pill" class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-2xl border border-slate-200/90 bg-white px-4 py-2.5 text-sm shadow-sm ring-1 ring-slate-100">
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Saldo</span>
                                        <strong id="token-balance-value" data-live-token-balance class="text-base font-semibold tabular-nums text-slate-900">{{ number_format($tokenBalance ?? 0, 0, ',', '.') }}</strong>
                                    </div>
                                    <span class="hidden h-4 w-px bg-slate-200 sm:block" aria-hidden="true"></span>
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Nesta visita</span>
                                        <strong id="chatkit-session-tokens-used" class="text-base font-semibold tabular-nums text-amber-700">0</strong>
                                    </div>
                                </div>
                            @else
                                <span id="token-balance-pill" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200/90 bg-slate-50 px-4 py-2 text-sm shadow-sm">
                                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Saldo</span>
                                    <strong id="token-balance-value" data-live-token-balance class="font-semibold tabular-nums text-slate-900">{{ number_format($tokenBalance ?? 0, 0, ',', '.') }}</strong>
                                </span>
                            @endif
                            <a href="{{ route('tokens.purchase') }}"
                               class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/25 transition hover:from-indigo-500 hover:to-violet-500 hover:shadow-lg hover:shadow-indigo-500/30 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <svg class="h-4 w-4 shrink-0 opacity-90" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                </svg>
                                Comprar tokens
                            </a>
                        </div>
                    </div>
                    @endif

                    @if($agent->isChatKitWorkflow())
                    @php
                        $compactTrailCvOnly = ($compactTrailChatUi ?? false) && (($compactTrailStep->slug ?? null) === 'cv');
                        $ckLib = $documentLibrary ?? ['cvs' => [], 'jds' => [], 'defaults' => ['cv_document_id' => null, 'jd_document_id' => null]];
                        $ckDefCv = $ckLib['defaults']['cv_document_id'] ?? null;
                        $ckDefJd = $ckLib['defaults']['jd_document_id'] ?? null;
                        $ckMaxCv = (int) ($ckLib['max_cv_body_chars'] ?? config('agent_documents.max_cv_body_chars', 60000));
                        $ckMaxJd = (int) ($ckLib['max_jd_body_chars'] ?? config('agent_documents.max_jd_body_chars', 60000));
                        $ckConsultTokens = (int) ($chatkitConsultationTokens ?? 0);
                    @endphp
                    @if ($chatkitSimpleChat ?? false)
                        @include('agents.partials.chatkit-workspace-simple')
                    @else
                        @include('agents.partials.chatkit-workspace', [
                            'compactTrail' => $compactTrailChatUi ?? false,
                            'compactCvOnly' => $compactTrailCvOnly,
                        ])
                    @endif
                    @else
                    <div id="cv-reuse-banner" class="hidden mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950"></div>
                    <!-- Interface de chat -->
                    <div class="border border-gray-300 rounded-lg">
                        <!-- Área de exibição do chat -->
                        <div id="chatBox" class="border mb-4 h-80 overflow-y-scroll p-3 text-sm leading-snug text-gray-800"></div>

                        <!-- Área de entrada de mensagem -->
                        <div class="border-t border-gray-300 p-3">

                            <div id="dynamic-inputs" class="mb-3 text-sm"></div>

                            <form id="message-form" class="flex gap-2">
                                <input type="text" id="userMessage"
                                    class="mr-0 min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                    placeholder="Digite sua mensagem...">
                                <button type="submit"
                                        class="shrink-0 rounded-lg bg-blue-500 px-3 py-2 text-sm text-white hover:bg-blue-600">
                                    Enviar
                                </button>
                            </form>

                            <div class="flex left mr-4 mt-2">
                                <button id="restartProcess" class="bg-gray-200 hover:bg-gray-400 text-gray px-4 py-2 rounded-lg text-sm">
                                    Reiniciar Processo
                                </button>
                            </div>

                            @if(!$session->already_rated)
                                <div class="flex left mr-4 mt-2">
                                     <button onclick="showRatingModal({{ $session->id }}, @js($session->agent->name))"
                                            class="px-2 py-1 bg-yellow-500 text-white text-xs rounded hover:bg-yellow-600">
                                        Avaliar
                                    </button>
                                </div>
                            @else
                                <span class="text-sm text-green-600">✅ Avaliado</span>
                            @endif

                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
        <div id="modal-container"></div>
    </div>

    @push('scripts')
@unless($agent->isChatKitWorkflow())
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
@endunless

@if(!$agent->isChatKitWorkflow())
<script>

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado');
    
    // Variáveis de estado
    let isSending = false;
    let systemPrompt = '';
    let agentDescription = '';
    let steps = [];
    let currentStepIndex = 0;
    let uploadedFiles = {};
    let process_ended = false;
    const agentId = {{ $agent->id }};
    const agentAiModel = "{{ $agent->model_type }}";

    console.log('model type',agentAiModel);
    let chatSessionId = null;
    const baseUrl = window.location.origin;

    // Elementos DOM frequentemente acessados
    const chatBox = document.getElementById('chatBox');
    const messageForm = document.getElementById('message-form');
    const userMessageInput = document.getElementById('userMessage');
    const dynamicInputsContainer = document.getElementById('dynamic-inputs');

    async function clearSavedCvProfile() {
        if (!confirm('Remover o CV guardado para este agente? Na próxima conversa voltará a pedir o CV.')) {
            return;
        }
        const res = await fetch('{{ route('chat.savedCv.destroy') }}?agent_id=' + encodeURIComponent(agentId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        const data = await res.json().catch(function () { return {}; });
        if (res.ok) {
            if (window.chatApp) {
                window.chatApp.showSuccessMessage(data.message || 'CV removido.');
            }
            location.reload();
        } else if (window.chatApp) {
            window.chatApp.showErrorMessage(data.message || 'Não foi possível remover o CV guardado.');
        }
    }

    function promptSaveCvAfterUpload() {
        const wrap = document.createElement('div');
        wrap.className = 'border border-green-200 bg-green-50 p-3 my-2 rounded text-sm text-gray-800';
        wrap.innerHTML = '<p class="mb-2">Quer <strong>guardar este CV</strong> no sistema? Nas próximas conversas com este agente só precisará enviar a <strong>nova vaga (JD)</strong>.</p>' +
            '<button type="button" class="btn-save-cv-yes mr-2 bg-green-600 text-white px-3 py-1 rounded text-sm">Sim, guardar</button>' +
            '<button type="button" class="btn-save-cv-no bg-gray-200 px-3 py-1 rounded text-sm">Não</button>';
        chatBox.appendChild(wrap);
        chatBox.scrollTop = chatBox.scrollHeight;

        wrap.querySelector('.btn-save-cv-no').addEventListener('click', function () {
            wrap.remove();
        });
        wrap.querySelector('.btn-save-cv-yes').addEventListener('click', async function () {
            try {
                const res = await fetch('{{ route('chat.savedCv.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ agent_id: agentId, session_id: chatSessionId }),
                });
                const data = await res.json().catch(function () { return {}; });
                if (res.ok && window.chatApp) {
                    window.chatApp.showSuccessMessage(data.message || 'CV guardado.');
                } else if (window.chatApp) {
                    window.chatApp.showErrorMessage(data.message || 'Não foi possível guardar o CV.');
                }
            } catch (e) {
                if (window.chatApp) {
                    window.chatApp.showErrorMessage('Erro ao guardar o CV.');
                }
            }
            wrap.remove();
        });
    }

    // Inicialização
    initChat();

    // Funções principais
    function initChat() {
        displayWelcomeMessage();
        loadAgentInstructions();
        setupEventListeners();
        checkCurrentStep(); // Nova função para verificar o passo atual
    }

    function displayWelcomeMessage() {
        chatBox.innerHTML = `<div><strong>Agente:</strong> Olá! Eu sou {{ $agent->name }}. Te ajudarei com as seguintes tarefas:</div>`;
    }

     // Verifica se há uma sessão ativa e qual o passo atual
     async function checkCurrentStep() {
        try {
            const res = await fetch(`/agents/{{ $agent->id }}/current-step`);
            const data = await res.json();
            
                // Se existe uma sessão ativa, salva o ID
                if (data.session_id) {
                    chatSessionId = data.session_id;
                    if (data.current_step > 0) { 
                        currentStepIndex = data.current_step - 1 || 0;
                    } else {
                        currentStepIndex = 0;
                    }
                } 

                console.log('Sessão ativa encontrada:', chatSessionId, 'Passo atual:', currentStepIndex);

                const banner = document.getElementById('cv-reuse-banner');
                if (banner) {
                    if (data.reused_saved_cv) {
                        banner.classList.remove('hidden');
                        banner.innerHTML = '<p class="mb-2">Usamos o <strong>CV padrão</strong> da sua biblioteca para este agente. Envie apenas a <strong>nova vaga (JD)</strong> abaixo.</p>' +
                            '<button type="button" class="text-sm text-red-700 underline" id="btn-clear-saved-cv">Apagar todos os CVs da biblioteca e voltar a pedir CV na próxima vez</button>';
                        const clearBtn = document.getElementById('btn-clear-saved-cv');
                        if (clearBtn) {
                            clearBtn.addEventListener('click', clearSavedCvProfile);
                        }
                    } else {
                        banner.classList.add('hidden');
                        banner.innerHTML = '';
                    }
                }

                // Se há um input requerido neste passo, renderiza-o
                console.log('Current_Step: ', data.current_step);
                if (data.upload_file) {
                    renderDynamicInputs(data.required_input);
                } else if (data.current_step > 0) { 
                        showStep(steps[currentStepIndex])
                } else {
                    process_ended = true;
                    console.log('process_ended = True : ', process_ended);
                }    
                      
        } catch (error) {
            console.error("Erro ao verificar passo atual:", error);
        }
    }

   // Verifica se há uma sessão ativa e qual o passo atual
   async function getNextStep() {
        try {
            const res = await fetch(`/agents/{{ $agent->id }}/next-step`);
            //const res = await fetch(`/agents/{{ $agent->id }}/chat`);
        
            const data = await res.json();

            if (data.current_step === 0) {
                process_ended = true;
            } else {
                process_ended = false;
            }
           
            // Se existe uma sessão ativa, salva o ID
            if (data.session_id) {
                chatSessionId = data.session_id;
                currentStepIndex = data.current_step - 1|| 0;
                console.log('Sessão ativa encontrada:', chatSessionId, 'Passo atual:', currentStepIndex);
                
                // Se há um input requerido neste passo, renderiza-o
                if (data.upload_file) {
                    renderDynamicInputs(data.required_input);
                }
            }
        } catch (error) {
            console.error("Erro ao verificar passo atual:", error);
        }
    }

     // Verifica se há uma sessão ativa e qual o passo atual
   async function startNewProcess() {

        try {
            process_ended = true;


            const res = await fetch(`/chat/${agentId}/finalize`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            if (!res.ok) {
                throw new Error(`Erro na requisição: ${res.status}`);
            }

            const data = await res.json();

            if (data.agent) {
                console.log('Agente:', data.agent);
                console.log('Nova sessão iniciada:', data.session_id);
                console.log('Passo atual:', data.current_step);

                // Atualiza as variáveis de estado
                chatSessionId = data.session_id;
                currentStepIndex = data.current_step - 1 || 0;
                process_ended = false;

                // Reinicia o chat
                initChat();
            }
        } catch (error) {
            console.error('Erro ao reiniciar o processo:', error);
        }

    }

    function setupEventListeners() {

        // Botao reestartar processo
       document.getElementById('restartProcess').addEventListener('click', startNewProcess);

        // Formulário de mensagem
        messageForm.addEventListener('submit', function(event) {
            event.preventDefault();
            sendMessage();
            checkCurrentStep();
        });    

    }
   
    function renderDynamicInputs(inputType) {
        if (!inputType) {
            dynamicInputsContainer.innerHTML = '';
            return;
        }
        
        console.log("Renderizando input para:", inputType);
        
        dynamicInputsContainer.innerHTML = `
            <div class="border p-3 rounded-lg bg-blue-50">
                <label class="block mb-2 text-sm font-medium text-gray-700">Envie o arquivo: ${inputType.toUpperCase()}</label>
                <input type="file" id="fileInput-${inputType}" class="mb-2 border border-gray-300 rounded px-2 py-1 w-full">
                <button type="button" onclick="handleFileUpload('${inputType}')" class="bg-blue-500 text-white px-4 py-1 rounded">
                    Enviar Arquivo
                </button>
            </div>
        `;
    }

    // Define a função handleFileUpload no escopo global
    window.handleFileUpload = function(tipo) {
        
        console.log('Arquivo enviado para:', tipo);
        const input = document.getElementById(`fileInput-${tipo}`);

        if (!input || !input.files.length) {
            alert('Selecione um arquivo primeiro.');
            return;
        }
        
        const file = input.files[0];
        uploadedFiles[tipo] = file;
        
        console.log(`Arquivo ${tipo} selecionado:`, file.name, 'CurrentStepIndex', currentStepIndex); 
        chatBox.innerHTML += `<div class="text-sm text-green-600 p-2">✅ Arquivo ${tipo.toUpperCase()} recebido: ${file.name}</div>`;
        chatBox.scrollTop = chatBox.scrollHeight;
        
        // Envia o arquivo automaticamente após upload
        //sendMessage(`Arquivo ${tipo.toUpperCase()} enviado: ${file.name}`);
        sendFile(tipo, `Arquivo ${tipo.toUpperCase()} enviado: ${file.name}`);
    }

    async function loadAgentInstructions() {
        console.log("Carregando instruções do agente...");
        try {
            const res = await fetch(`/agents/{{ $agent->id }}/instructions`);
            const data = await res.json();
            
            systemPrompt = data.system_prompt;
            chatBox.innerHTML += `<div class="text-600 p-2">${data.agent_description}</div>`;

            if (data.steps) {
                steps = data.steps;
           
                console.log('Instruções do agente carregadas - data:', data);
                console.log('Instruções carregadas:', steps, steps.length, currentStepIndex);
            } else {
                steps = null;
                currentStepIndex = 0;
                console.log('Nenhum passo encontrado.');
            }
            
        } catch (error) {
            console.error("Erro ao carregar instruções do agente:", error);
            chatBox.innerHTML += `<div class="text-red-600 p-2">Erro ao carregar passos do agente. Tente novamente.</div>`;
        }
    }

    function showStep(step) {
        if (!step) return;
        
        const message = `Passo ${step.order}: ${step.description}`;
        console.log('Exibindo passo:', step);
        chatBox.innerHTML += `<div class="text-sm p-2 bg-gray-100 my-2 rounded">${message}</div>`;
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function sendFile(tipo, customMessage) {
   
        // Usa mensagem personalizada ou pega do input
        let userMessage = customMessage;
        let fileTipo = tipo;

        // Exibe a mensagem do usuário no chat
        chatBox.innerHTML += `<div class="my-2"><strong>Você:</strong> ${userMessage}</div>`;
        chatBox.scrollTop = chatBox.scrollHeight;

        console.log('Enviando mensagem:', userMessage, 'Session ID:', chatSessionId); 

        // Prepara dados para envio
        const formData = new FormData();
        formData.append('message', userMessage);
        formData.append('agent_id', agentId);
        formData.append('ai_model', agentAiModel);
        formData.append('inputType', fileTipo);

        // CRUCIAL: Sempre envia o session_id se existir
        if (chatSessionId) {
            formData.append('session_id', chatSessionId);
            console.log('Anexando session_id ao request:', chatSessionId);
        }

        // Anexa arquivos caso existam
        for (const [key, file] of Object.entries(uploadedFiles)) {
            formData.append(key, file);
            console.log(`Anexando arquivo ${key}:`, file.name);
        }

        // Exibe indicador de carregamento
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'text-gray-500 italic my-2';
        loadingIndicator.textContent = 'Enviando mensagem...';
        chatBox.appendChild(loadingIndicator);
        chatBox.scrollTop = chatBox.scrollHeight;

        fetch('{{ route('chat.sendfile') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async response => {
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Erro da API:', errorText);
                throw new Error('Erro na resposta do servidor');
            }
            return response.json();
        })
        .then(data => {
            // Remove o indicador de carregamento
            chatBox.removeChild(loadingIndicator);
            
            // IMPORTANTE: Atualiza o sessionId
            if (data.session_id) {
                chatSessionId = data.session_id;
                console.log('Session ID atualizado:', chatSessionId);
            }

            if (data.next_required_input && data.next_required_input !== 'continue') {
                if (data.next_upload_file) {
                    renderDynamicInputs(data.next_required_input);
                }
            }

            if (data.offer_save_cv) {
                promptSaveCvAfterUpload();
            }

            // Limpa o campo de mensagem
            userMessageInput.value = '';
            isSending = false;
        })
        .catch(error => {
            console.error('Erro ao enviar mensagem:', error);
            
            // Remove o indicador de carregamento
            if (loadingIndicator.parentNode === chatBox) {
                chatBox.removeChild(loadingIndicator);
            }

            // Exibe mensagem de erro
            chatBox.innerHTML += `<div class="text-red-600 p-2">❌ Erro ao enviar mensagem. Por favor, tente novamente.</div>`;
            chatBox.scrollTop = chatBox.scrollHeight;
            
            isSending = false;
        });
    }


    function sendMessage(customMessage) {
        if (isSending) return;
        isSending = true;

        // Usa mensagem personalizada ou pega do input
        let userMessage = customMessage || userMessageInput.value.trim();
        
        // Se não há mensagem explícita
        if (!userMessage) {
            if (Object.keys(uploadedFiles).length > 0) {
                userMessage = "Arquivo enviado com sucesso.";
            } else {
                userMessage = "Por favor, digite uma mensagem ou envie um arquivo.";
                isSending = false;
                return;
            }
        }

        // Exibe a mensagem do usuário no chat
        chatBox.innerHTML += `<div class="my-2"><strong>Você:</strong> ${userMessage}</div>`;
        chatBox.scrollTop = chatBox.scrollHeight;

        console.log('Enviando mensagem:', userMessage, 'Session ID:', chatSessionId); 

        // Prepara dados para envio
        const formData = new FormData();
        formData.append('message', userMessage);
        formData.append('agent_id', agentId);
        formData.append('ai_model', agentAiModel);

        // CRUCIAL: Sempre envia o session_id se existir
        if (chatSessionId) {
            formData.append('session_id', chatSessionId);
            console.log('Anexando session_id ao request:', chatSessionId);
        }

        // Anexa arquivos caso existam
        for (const [key, file] of Object.entries(uploadedFiles)) {
            formData.append(key, file);
            console.log(`Anexando arquivo ${key}:`, file.name);
        }

        // Exibe indicador de carregamento
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'text-gray-500 italic my-2';
        loadingIndicator.textContent = 'Enviando mensagem...';
        chatBox.appendChild(loadingIndicator);
        chatBox.scrollTop = chatBox.scrollHeight;

        fetch('{{ route('chat.send') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                if (response.status === 402 && data.error === 'insufficient_tokens') {
                    const msg = data.message || 'Tokens insuficientes.';
                    throw new Error('TOKENS:' + msg);
                }
                console.error('Erro da API:', data);
                throw new Error(data.message || 'Erro na resposta do servidor');
            }
            return data;
        })
        .then(data => {
            // Remove o indicador de carregamento
            chatBox.removeChild(loadingIndicator);

            let replyHtml;

            // Verifica se o conteúdo é Markdown ou HTML
            if (data.reply.startsWith('<') && data.reply.endsWith('>')) {
                // Presume que é HTML
                replyHtml = data.reply;
            } else {
                // Presume que é Markdown
                replyHtml = marked.parse(data.reply);
            }

            // Wrap do conteúdo com Tailwind e aplicação de estilos na tabela
           // Aplica Tailwind dinamicamente nas tags da tabela
            replyHtml = replyHtml
                .replace(/<table[^>]*>/g, '<table class="min-w-full table-auto border border-gray-300 divide-y divide-gray-300 text-sm text-gray-800">')
                .replace(/<th>/g, '<th class="bg-gray-100 font-semibold text-left px-4 py-2 border border-gray-300">')
                .replace(/<td>/g, '<td class="px-4 py-2 border border-gray-200 align-top">');

            // Exibe com wrapper estilizado
            chatBox.innerHTML += `
            <div class="bg-white p-4 my-4 rounded-lg border border-gray-200 text-sm prose prose-sm prose-slate max-w-full overflow-auto">
                <strong>Agente (${data.model_used}):</strong><br>
                <div class="overflow-x-auto">
                    ${replyHtml}
                </div>
            </div>`;

            // Processa as fórmulas matemáticas com MathJax
            MathJax.typesetPromise();

            chatBox.scrollTop = chatBox.scrollHeight;
            
            // IMPORTANTE: Atualiza o sessionId
            if (data.session_id) {
                chatSessionId = data.session_id;
                console.log('Session ID atualizado:', chatSessionId);
            }

            if (typeof data.token_balance === 'number' && typeof window.setDisplayedUserTokenBalance === 'function') {
                window.setDisplayedUserTokenBalance(data.token_balance);
            }

            // Limpa o campo de mensagem
            userMessageInput.value = '';
            isSending = false;
        })
        .catch(error => {
            console.error('Erro ao enviar mensagem:', error);
            
            // Remove o indicador de carregamento
            if (loadingIndicator.parentNode === chatBox) {
                chatBox.removeChild(loadingIndicator);
            }

            let errMsg = '❌ Erro ao enviar mensagem. Por favor, tente novamente.';
            if (error.message && error.message.startsWith('TOKENS:')) {
                errMsg = '⚠️ ' + error.message.replace(/^TOKENS:/, '');
            }
            chatBox.innerHTML += `<div class="text-red-600 p-2">${errMsg}</div>`;
            chatBox.scrollTop = chatBox.scrollHeight;
            
            isSending = false;
        });
    }


  // Expor funções globalmente para uso no modal
  window.chatApp = {
        showSuccessMessage: function(message) {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        },
        
        showErrorMessage: function(message) {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    };

});
</script>
@else
@include('agents.partials.chatkit-main-scripts')
@endif

@include('agents.partials.chatkit-rating-scripts')
    @endpush
</x-app-layout>
