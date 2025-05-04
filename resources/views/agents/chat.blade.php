<!-- resources/views/agents/chat.blade.php -->
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
                    
                    <!-- Seletor de Modelo AI -->
                    <div class="mb-4">
                        <label for="ai-model" class="block text-sm font-medium text-gray-700">Selecione o modelo:</label>
                        <select id="ai-model" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <option value="gpt-4">OpenAI (GPT)</option>
                            <option value="claude-3">Anthropic (Claude)</option>
                        </select>
                    </div>
                    
                    <!-- Interface de chat -->
                    <div class="border border-gray-300 rounded-lg">
                        <!-- Área de exibição do chat -->
                        <div id="chatBox" class="p-4 border h-80 overflow-y-scroll mb-4"></div>

                        <!-- Área de entrada de mensagem -->
                    ≈

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
                        </div>
                    </div>
                    
                    <!-- Informação do modelo ativo -->
                    <div class="mt-4 text-sm text-gray-600" id="model-info">
                        <p>Modelo atual: <span class="font-semibold">OpenAI (GPT)</span></p>
                    </div>
                   
                </div>
            </div>
        </div>
    </div>
    
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            
            console.log('DOM carregado'); // debug
            
            // Variáveis de estado
            let isSending = false;
            let systemPrompt = '';
            let steps = [];
            let currentStepIndex = 0;
            let uploadedFiles = {};
            const agentId = {{ $agent->id }};
            let chatSessionId = null;
            const baseUrl = window.location.origin;

            // Elementos DOM frequentemente acessados
            const chatBox = document.getElementById('chatBox');
            const messageForm = document.getElementById('message-form');
            const userMessageInput = document.getElementById('userMessage');
            const aiModelSelect = document.getElementById('ai-model');
            const dynamicInputsContainer = document.getElementById('dynamic-inputs');

           // Inicialização
           initChat();

           // Funções principais
           function initChat() {
                displayWelcomeMessage();
                loadAgentInstructions();
                setupEventListeners();
            }

            function displayWelcomeMessage() {
                chatBox.innerHTML = `<div><strong>Agente:</strong> Olá! Eu sou {{ $agent->name }}. Te ajudarei com as seguintes tarefas:</div>`;
            }

            async function loadAgentInstructions() {
                console.log("Carregando instruções do agente...");
                try {
                    const res = await fetch(`/agents/{{ $agent->id }}/instructions`);
                    const data = await res.json();
                    
                    systemPrompt = data.system_prompt;
                    steps = data.steps;
                    
                    console.log('Instruções carregadas:', steps);
                    
                    if (steps.length > 0) {
                        showStep(steps[currentStepIndex]);
                        const requiredInputs = steps[currentStepIndex].required_inputs;
                        if (requiredInputs && requiredInputs.length > 0) {
                            renderDynamicInputs(requiredInputs[0]);
                        }
                    }
                } catch (error) {
                    console.error("Erro ao carregar instruções do agente:", error);
                    chatBox.innerHTML += `<div class="text-red-600 p-2">Erro ao carregar passos do agente. Tente novamente.</div>`;
                }
            }

            function setupEventListeners() {
                // Formulário de mensagem
                messageForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    sendMessage();
                });
                
                // Alteração de modelo de IA
                aiModelSelect.addEventListener('change', function() {
                    const modelInfo = document.getElementById('model-info');
                    const selectedModel = this.options[this.selectedIndex].text;
                    modelInfo.innerHTML = `<p>Modelo atual: <span class="font-semibold">${selectedModel}</span></p>`;
                });
            }

            function showStep(step) {
                const message = `Passo ${step.order}: ${step.description}`;
                console.log('Exibindo passo:', step);
                chatBox.innerHTML += `<div class="text-sm p-2 bg-gray-100 my-2 rounded">${message}</div>`;
                chatBox.scrollTop = chatBox.scrollHeight;
            }

            function renderDynamicInputs(inputType) {
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
                
                // Define a função handleFileUpload no escopo global
                window.handleFileUpload = function(tipo) {
                    const input = document.getElementById(`fileInput-${tipo}`);
                    if (!input.files.length) {
                        alert('Selecione um arquivo primeiro.');
                        return;
                    }
                    
                    const file = input.files[0];
                    uploadedFiles[tipo] = file;
                    
                    console.log(`Arquivo ${tipo} selecionado:`, file.name);
                    chatBox.innerHTML += `<div class="text-sm text-green-600 p-2">✅ Arquivo ${tipo.toUpperCase()} recebido: ${file.name}</div>`;
                    chatBox.scrollTop = chatBox.scrollHeight;
                    
                    checkRequiredInputsAndProceed();
                };
            }

            function checkRequiredInputsAndProceed() {
                if (currentStepIndex >= steps.length) {
                    console.log('Todos os passos foram concluídos');
                    return;
                }
                
                const step = steps[currentStepIndex];
                const requiredInputs = step.required_inputs;
                
                // Verifica se todos os arquivos necessários foram enviados
                const allUploaded = requiredInputs.every(input => uploadedFiles[input]);
                
                if (allUploaded) {
                    console.log('Todos os arquivos necessários foram enviados. Enviar mensagem ao agente.');
                    
                    // Envia uma mensagem automática para processar os arquivos
                    userMessageInput.value = `Por favor, processe os arquivos que enviei para o passo ${step.order}.`;
                    sendMessage();
                }
            }

            function nextStep() {
                currentStepIndex++;
                console.log('Next steps'); // debug
                if (currentStepIndex < steps.length) {
                showStep(steps[currentStepIndex]);
                } else {
                document.getElementById('chat-box').innerHTML += `<div class="text-green-600">Todos os passos foram concluídos!</div>`;
                }
            }

            function sendMessage() {
                console.log('sendMessage iniciada'); // debug

                if (event) event.preventDefault(); // previne reload se for via form

                if (isSending) return;
                isSending = true;

                // Exibe a mensagem do usuário no chat
                chatBox.innerHTML += `<div class="my-2"><strong>Você:</strong> ${userMessage}</div>`;
                chatBox.scrollTop = chatBox.scrollHeight;

                // Prepara dados para envio
                const formData = new FormData();
                if (!userMessage) {
                    userMessage = `continue`;
                }
                formData.append('message', userMessage);
                formData.append('agent_id', agentId);
                formData.append('ai_model', aiModelSelect.value);

                if (chatSessionId) {
                    formData.append('session_id', chatSessionId);
                }

                // Anexa arquivos caso existam
                for (const [key, file] of Object.entries(uploadedFiles)) {
                    formData.append(key, file);
                }

                // Exibe indicador de carregamento
                const loadingIndicator = document.createElement('div');
                loadingIndicator.className = 'text-gray-500 italic my-2';
                loadingIndicator.textContent = 'Enviando mensagem...';
                chatBox.appendChild(loadingIndicator);
                chatBox.scrollTop = chatBox.scrollHeight;

                
                // Verificar se é necessário manter esse codigo
                if (!userMessage) {
                    alert('Por favor, digite uma mensagem.');
                    sendButton.disabled = false;
                    sendButton.textContent = 'Enviar';
                    isSending = false;
                return;
                }

                // Envia a requisição
                fetch('{{ route('chat.send') }}', {
                    method: 'POST',
                    headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
                    
                    // Exibe a resposta do agente
                    chatBox.innerHTML += `<div class="bg-gray-50 p-2 my-2 rounded"><strong>Agente (${data.model_used}):</strong> ${data.reply}</div>`;
                    chatBox.scrollTop = chatBox.scrollHeight;
                    
                    // Guarda o ID da sessão
                    if (data.session_id) {
                        chatSessionId = data.session_id;
                    }

                    // Verifica se tem próximo input requerido
                    if (data.next_required_input) {
                        console.log('Próximo input requerido:', data.next_required_input);
                        renderDynamicInputs(data.next_required_input);
                    } else {
                        // Se não há mais inputs requeridos neste passo
                        currentStepIndex++;
                        
                        // Limpa os arquivos já processados
                        uploadedFiles = {};
                        
                        // Verifica se há mais passos
                        if (currentStepIndex < steps.length) {
                            // Exibe o próximo passo
                            showStep(steps[currentStepIndex]);
                            
                            // Renderiza os inputs do próximo passo
                            const nextRequiredInputs = steps[currentStepIndex].required_inputs;
                            if (nextRequiredInputs && nextRequiredInputs.length > 0) {
                                renderDynamicInputs(nextRequiredInputs[0]);
                            } else {
                                dynamicInputsContainer.innerHTML = '';
                            }
                        } else {
                            // Finaliza o processo, todos os passos concluídos
                            chatBox.innerHTML += `<div class="text-green-600 p-2 font-bold">✅ Todos os passos foram concluídos! Você pode continuar conversando com o agente.</div>`;
                            dynamicInputsContainer.innerHTML = '';
                        }
                    }

                     // Limpa o campo de mensagem
                    userMessageInput.value = '';
                    isSending = false;

                    })
                    .catch(error => {
                        console.error('Erro ao enviar mensagem:', error);
                        
                        // Remove o indicador de carregamento
                        chatBox.removeChild(loadingIndicator);

                        // Exibe mensagem de erro
                        chatBox.innerHTML += `<div class="text-red-600 p-2">❌ Erro ao enviar mensagem. Por favor, tente novamente.</div>`;
                        chatBox.scrollTop = chatBox.scrollHeight;
                        
                        isSending = false;
                    })
            }

            
            // ✅ Fecha corretamente essa função
            document.getElementById('ai-model').addEventListener('change', function() {
                const modelInfo = document.getElementById('model-info');
                const selectedModel = this.options[this.selectedIndex].text;
                modelInfo.innerHTML = `<p>Modelo atual: <span class="font-semibold">${selectedModel}</span></p>`;
            }); 

            document.getElementById('message-form').addEventListener('submit', function(event) {
                event.preventDefault(); // Impede o reload da página
                console.log('formulário enviado'); // debug
                sendMessage();
            });

            fetch(`/agents/{{ $agent->id }}/current-step`)
                .then(response => response.json())
                .then(data => {
                    if (data.required_input) {
                        renderDynamicInputs(data.required_input);
                    }
                });

        });

    </script>


</x-app-layout>