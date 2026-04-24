<script>
// Função para mostrar o modal de avaliação (global)
window.showRatingModal = function(chatSessionId, agentName) {
    console.log('Abrindo modal de avaliação:', chatSessionId, agentName);
    
    fetch(`/ratings/quick-modal/${chatSessionId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.html) {
                document.getElementById('modal-container').innerHTML = data.html;
                
                // Aguarda um momento para o HTML ser renderizado
                setTimeout(() => {
                    const modal = document.getElementById('ratingModal');
                    if (modal) {
                        modal.style.display = 'block';
                        initializeRatingModal();
                    } else {
                        console.error('Modal não encontrado após inserção do HTML');
                    }
                }, 100);
            } else {
                console.error('Resposta inválida:', data);
                window.chatApp.showErrorMessage('Erro ao carregar formulário de avaliação');
            }
        })
        .catch(error => {
            console.error('Erro ao carregar modal:', error);
            window.chatApp.showErrorMessage('Erro ao carregar formulário de avaliação');
        });
};

// Função para inicializar o modal de avaliação
function initializeRatingModal() {
    const modal = document.getElementById('ratingModal');
    const form = document.getElementById('quickRatingForm');
    
    if (!modal || !form) {
        console.error('Elementos do modal não encontrados');
        return;
    }

    // Inicializar estrelas
    initializeQuickRatingStars();
    
    // Event listener para o formulário
    form.addEventListener('submit', function(e) {
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
                window.chatApp.showSuccessMessage(data.message);
                closeRatingModal();
                
                // Atualizar o botão de avaliação se estiver na página
                const evaluateButton = document.querySelector('button[onclick*="showRatingModal"]');
                if (evaluateButton) {
                    evaluateButton.style.display = 'none';
                    const parent = evaluateButton.parentElement;
                    parent.innerHTML = '<span class="text-sm text-green-600">✅ Avaliado</span>';
                }
            } else {
                window.chatApp.showErrorMessage(data.message || 'Erro ao salvar avaliação');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            window.chatApp.showErrorMessage('Erro ao salvar avaliação');
        })
        .finally(() => {
            // Reabilitar botão
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        });
    });

    // Fechar modal ao clicar fora dele
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeRatingModal();
        }
    });
}

// Função para fechar o modal
window.closeRatingModal = function() {
    const modal = document.getElementById('ratingModal');
    if (modal) {
        modal.style.display = 'none';
    }
    // Limpar o container do modal
    document.getElementById('modal-container').innerHTML = '';
};

// Função para inicializar as estrelas
function initializeQuickRatingStars() {
    const stars = document.querySelectorAll('.quick-star-button');
    const ratingInput = document.getElementById('quickRatingValue');
    
    if (!stars.length || !ratingInput) {
        console.error('Elementos das estrelas não encontrados');
        return;
    }
    
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

// Função para atualizar a visualização das estrelas
function updateStarDisplay(stars, rating, isHover = false) {
    stars.forEach((star, index) => {
        const svg = star.querySelector('svg');
        if (svg) {
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
        }
    });
}

</script>