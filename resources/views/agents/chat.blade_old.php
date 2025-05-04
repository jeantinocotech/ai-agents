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
                    
                    <!-- Interface de chat -->
                    <div class="border border-gray-300 rounded-lg">
                        <!-- Área de mensagens -->
                        <div class="h-96 p-4 overflow-y-auto" id="chat-messages">
                            <div class="bg-gray-100 p-3 rounded-lg mb-4 max-w-3/4">
                                <p class="text-gray-800">Olá! Eu sou {{ $agent->name }}. Como posso ajudar você hoje?</p>
                            </div>
                            <!-- As mensagens aparecerão aqui -->
                        </div>
                        
                        <!-- Área de exibição do chat -->
                        <div id="chatBox" class="p-4 border h-80 overflow-y-scroll mb-4"></div>

                        <!-- Área de entrada de mensagem -->
                        <div class="border-t border-gray-300 p-4">
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
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('message-form').addEventListener('submit', function(event) {
            event.preventDefault(); // Impede o reload da página
            sendMessage();
        });

        function sendMessage() {
            let userMessage = document.getElementById('userMessage').value.trim();
            let agentId = {{ $agent->id }};

            if (!userMessage) {
                alert('Por favor, digite uma mensagem.');
                return;
            }

            fetch('{{ route('chat.send') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    message: userMessage,
                    agent_id: agentId
                })
            })
            .then(response => response.json())
            .then(data => {
                const chatBox = document.getElementById('chatBox');
                const userMessage = document.getElementById('userMessage').value;

                // Adiciona mensagem do usuário
                chatBox.innerHTML += `<div><strong>Você:</strong> ${userMessage}</div>`;

                // Primeira vez: saudação automática do backend (já salva no banco)
                if (!window.chatSessionId) {
                    chatBox.innerHTML += `<div><strong>Agente:</strong> ${data.reply}</div>`;
                } else {
                    // Nas próximas vezes, o reply será apenas a resposta normal
                    chatBox.innerHTML += `<div><strong>Agente:</strong> ${data.reply}</div>`;
                }

                // Limpa input
                document.getElementById('userMessage').value = '';

                // Salva o ID da sessão para uso nas próximas mensagens
                window.chatSessionId = data.session_id;

                // Scroll automático para o final
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(error => {
                console.error('Erro ao enviar mensagem:', error);
                alert('Erro ao enviar mensagem.');
            });
        }
  
        function askToFinalize(sessionId) {
        const save = confirm("Deseja salvar essa conversa para continuar depois?");
        
        fetch('{{ route('chat.finalize') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                session_id: sessionId,
                save: save
            })
        }).then(res => res.json())
        .then(data => alert(data.status));
    }
    
        // Exemplo de chamada da função askToFinalize
        // askToFinalize('12345'); // Substitua '12345' pelo ID da sessão real
</script>

</x-app-layout>