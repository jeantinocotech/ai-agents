<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center mb-6">
                        <a href="{{ route('agents.show', $agent->id) }}" class="text-blue-500 hover:underline mr-4">
                            &larr; Voltar para detalhes
                        </a>
                        <h2 class="text-2xl font-bold">Chat com {{ $agent->name }}</h2>
                    </div>
                    
                    <!-- Interface de chat -->
                    <div class="border border-gray-300 rounded-lg">
                        <!-- Área de exibição do chat -->
                        <div id="chatBox" class="p-4 border h-80 overflow-y-scroll mb-4"></div>

                        <!-- Área de entrada de mensagem -->
                        <div class="border-t border-gray-300 p-4">

                            <div id="dynamic-inputs" class="mb-4"></div>

                            <form id="message-form" class="flex">
                                <input type="text" id="userMessage" 
                                    class="flex-1 border border-gray-300 rounded-lg px-4 py-2 mr-2" 
                                    placeholder="Digite sua mensagem...">
                                <button type="submit" 
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                                    Enviar
                                </button>
                            </form>

                            <div class="flex left mr-4 mt-2 p-2">
                                <button id="restartProcess" class="bg-gray-200 hover:bg-gray-400 text-gray px-4 py-2 rounded-lg text-sm">
                                    Reiniciar Processo
                                </button>
                            </div>

                        </div>
                    </div>
                   
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

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

            if (data.current_step = 0) { 
                process_ended = true;
            } else {
                process_ended = false
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

            currentStepIndex

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

});

</script>