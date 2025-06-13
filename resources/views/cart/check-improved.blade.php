<x-app-layout>
    <div class="max-w-6xl mx-auto py-12 px-6">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Finalize sua compra</h1>
            <p class="text-gray-600">
                Preencha os dados abaixo para completar sua compra de forma segura.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Formulário de Checkout -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Dados do Comprador</h2>
                
                <form id="checkout-form" class="space-y-4" action="{{ route('cart.processCheckout') }}" method="POST" data-url="{{ route('cart.processCheckout') }}">
                    @csrf
                    <meta name="csrf-token" content="{{ csrf_token() }}">
                    
                    <!-- Dados Pessoais -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                            <input type="text" id="name" name="name" value="{{ $user->name }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="email" name="email" value="{{ $user->email }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <input type="tel" id="phone" name="phone" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="(11) 99999-9999">
                        </div>
                        
                        <div>
                            <label for="document" class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                            <input type="text" id="document" name="document" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="000.000.000-00">
                        </div>
                    </div>

                    <!-- Método de Pagamento -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Método de Pagamento</label>
                        
                        <div class="space-y-2">
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="credit_card" class="mr-3" checked>
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                    <span class="font-medium">Cartão de Crédito</span>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="pix" class="mr-3">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    <span class="font-medium">PIX</span>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="boleto" class="mr-3">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="font-medium">Boleto Bancário</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Dados do Cartão (mostrado apenas quando cartão selecionado) -->
                    <div id="card-details" class="mt-6 space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Dados do Cartão</h3>
                        
                        <div>
                            <label for="card_number" class="block text-sm font-medium text-gray-700 mb-1">Número do Cartão</label>
                            <input type="text" id="card_number" name="card_number"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="0000 0000 0000 0000">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="card_expiry" class="block text-sm font-medium text-gray-700 mb-1">Validade</label>
                                <input type="text" id="card_expiry" name="card_expiry"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="MM/AA">
                            </div>
                            
                            <div>
                                <label for="card_cvv" class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                                <input type="text" id="card_cvv" name="card_cvv"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="000">
                            </div>
                        </div>
                        
                        <div>
                            <label for="card_holder_name" class="block text-sm font-medium text-gray-700 mb-1">Nome do Portador</label>
                            <input type="text" id="card_holder_name" name="card_holder_name"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Nome como está no cartão">
                        </div>
                    </div>

                    <!-- Botão de Finalizar -->
                    <div class="mt-8">
                        <button type="submit" id="checkout-btn"
                                class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="button-text">Finalizar Compra</span>
                            <span id="spinner" class="hidden">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processando...
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Resumo do Pedido -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Resumo do Pedido</h2>
                
                <div class="space-y-4">
                    @foreach($agents as $agent)
                        <div class="flex justify-between py-2 border-b">
                            <div>
                                <h3 class="font-medium text-gray-900">{{ $agent->name }}</h3>
                                <p class="text-sm text-gray-600">{{ Str::limit($agent->description, 100) }}</p>
                            </div>
                            <div class="font-medium">
                                R$ {{ number_format($agent->price, 2, ',', '.') }}
                            </div>
                        </div>
                    @endforeach
                    
                    <div class="flex justify-between pt-4 font-bold text-lg">
                        <span>Total</span>
                        <span class="text-green-600">R$ {{ number_format($total, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript para manipulação do formulário -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos do formulário
            const form = document.getElementById('checkout-form');
            const submitButton = document.getElementById('checkout-btn');
            const buttonText = document.getElementById('button-text');
            const spinner = document.getElementById('spinner');
            const cardDetails = document.getElementById('card-details');
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            
            // Mostrar/ocultar detalhes do cartão com base no método de pagamento
            paymentMethods.forEach(method => {
                method.addEventListener('change', function() {
                    if (this.value === 'credit_card') {
                        cardDetails.classList.remove('hidden');
                    } else {
                        cardDetails.classList.add('hidden');
                    }
                });
            });
            
            // Inicializar - verificar o método selecionado
            const initialMethod = document.querySelector('input[name="payment_method"]:checked');
            if (initialMethod && initialMethod.value !== 'credit_card') {
                cardDetails.classList.add('hidden');
            }
            
            // Manipular envio do formulário
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Desabilitar botão e mostrar spinner
                submitButton.disabled = true;
                buttonText.classList.add('hidden');
                spinner.classList.remove('hidden');
                
                // Coletar dados do formulário
                const formData = new FormData(form);
                
                // Enviar dados via AJAX
                fetch(form.dataset.url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '/cart/success';
                    } else {
                        // Mostrar erro
                        alert(data.error || 'Ocorreu um erro ao processar o pagamento. Tente novamente.');
                        
                        // Reativar botão
                        submitButton.disabled = false;
                        buttonText.classList.remove('hidden');
                        spinner.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro ao processar o pagamento. Tente novamente.');
                    
                    // Reativar botão
                    submitButton.disabled = false;
                    buttonText.classList.remove('hidden');
                    spinner.classList.add('hidden');
                });
            });
        });
    </script>
</x-app-layout>