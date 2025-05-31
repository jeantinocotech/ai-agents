<?php
// resources/views/ratings/quick-modal.blade.php
?>
<div id="ratingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" style="display: none;">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
           <!-- Cabeçalho -->
           <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    Avalie {{ $chatSession->agent->name }}
                </h3>
                <button onclick="closeRatingModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Formulário -->
            <form id="quickRatingForm">
                @csrf
                <input type="hidden" name="agent_id" value="{{ $chatSession->agent_id }}">
                <input type="hidden" name="chat_session_id" value="{{ $chatSession->id }}">
                
                <!-- Estrelas -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sua avaliação:</label>
                    <div class="flex items-center space-x-1" id="quickRatingStars">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button" class="quick-star-button focus:outline-none" data-value="{{ $i }}">
                                <svg class="h-8 w-8 {{ ($existingRating && $existingRating->rating >= $i) ? 'text-yellow-500' : 'text-gray-300' }} hover:text-yellow-400 transition-colors" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </button>
                        @endfor
                        <input type="hidden" name="rating" id="quickRatingValue" value="{{ $existingRating ? $existingRating->rating : '0' }}" required>
                    </div>
                </div>
                
                <!-- Comentário -->
                <div class="mb-4">
                    <label for="quickComment" class="block text-sm font-medium text-gray-700 mb-2">Comentário (opcional):</label>
                    <textarea 
                        id="quickComment" 
                        name="comment" 
                        rows="3" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" 
                        placeholder="Compartilhe sua experiência..."
                    >{{ $existingRating ? $existingRating->comment : '' }}</textarea>
                </div>
                
                <!-- Botões -->
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeRatingModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                        Pular
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                        {{ $existingRating ? 'Atualizar' : 'Enviar' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// JavaScript para o modal de avaliação rápida
function showRatingModal(chatSessionId) {
    fetch(`/ratings/quick-modal/${chatSessionId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('ratingModal').style.display = 'block';
            initializeQuickRatingStars();
        })
        .catch(error => {
            console.error('Erro ao carregar modal:', error);
        });
}

function closeRatingModal() {
    document.getElementById('ratingModal').style.display = 'none';
}

function initializeQuickRatingStars() {
    const stars = document.querySelectorAll('.quick-star-button');
    const ratingInput = document.getElementById('quickRatingValue');
    
    stars.forEach(star => {
        star.addEventListener('click', (e) => {
            e.preventDefault();
            const value = parseInt(star.dataset.value);
            ratingInput.value = value;
            
            // Atualizar visualização das estrelas
            updateStarDisplay(stars, value);
        });
        
        star.addEventListener('mouseover', () => {
            const value = parseInt(star.dataset.value);
            updateStarDisplay(stars, value, true);
        });
        
        star.addEventListener('mouseout', () => {
            const currentRating = parseInt(ratingInput.value);
            updateStarDisplay(stars, currentRating);
        });
    });
}

function updateStarDisplay(stars, rating, isHover = false) {
    stars.forEach((star, index) => {
        const svg = star.querySelector('svg');
        svg.classList.remove('text-yellow-500', 'text-yellow-400', 'text-gray-300');
        
        if (index < rating) {
            if (isHover) {
                svg.classList.add('text-yellow-400');
            } else {
                svg.classList.add('text-yellow-500');
            }
        } else {
            svg.classList.add('text-gray-300');
        }
    });
}

// Envio do formulário via AJAX
document.getElementById('quickRatingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    
    // Desabilitar botão durante envio
    submitButton.disabled = true;
    submitButton.textContent = 'Enviando...';
    
    fetch('/ratings/quick-store', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensagem de sucesso
            showSuccessMessage(data.message);
            closeRatingModal();
        } else {
            showErrorMessage(data.message || 'Erro ao salvar avaliação');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showErrorMessage('Erro ao salvar avaliação');
    })
    .finally(() => {
        // Reabilitar botão
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    });
});

function showSuccessMessage(message) {
    // Criar notificação de sucesso
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Remover após 3 segundos
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function showErrorMessage(message) {
    // Criar notificação de erro
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Remover após 3 segundos
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Fechar modal ao clicar fora dele
document.getElementById('ratingModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRatingModal();
    }
});

// Inicializar ao carregar a página se o modal estiver visível
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('ratingModal').style.display === 'block') {
        initializeQuickRatingStars();
    }
});
</script>

<?php
/*
COMO USAR ESTE MODAL:

1. Inclua este arquivo na sua view principal ou layout
2. Chame showRatingModal(chatSessionId) quando quiser mostrar o modal
3. Exemplo de uso após finalizar um chat:

// No final da sua função de chat
<script>
// Mostrar modal automaticamente após 2 segundos
setTimeout(() => {
    showRatingModal({{ $chatSession->id }});
}, 2000);
</script>

// Ou em um botão
<button onclick="showRatingModal({{ $chatSession->id }})" class="btn btn-primary">
    Avaliar Experiência
</button>
*/
?>