<x-app-layout>
    <div class="max-w-4xl mx-auto py-12 px-6">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Finalize sua compra</h1>
            <p class="text-gray-600">
                Clique no botão "Comprar" ao lado de cada agente para ser redirecionado para o checkout seguro da Hotmart.
            </p>
        </div>

        @if (session('error'))
            <div class="mb-6 p-4 bg-red-100 text-red-800 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="space-y-4 mb-8">
            @foreach($agents as $agent)
                <div class="bg-white p-6 border rounded-lg shadow-sm flex justify-between items-center">
                    <div class="flex-1">
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ $agent->name }}</h2>
                        <p class="text-gray-600 mb-3">{{ $agent->description }}</p>
                        <div class="text-lg font-bold text-green-600">
                            R$ {{ number_format($agent->price, 2, ',', '.') }}
                        </div>
                    </div>

                    <div class="ml-6">
                        @if($agent->hotmart_checkout_url)
                            <a href="{{ $agent->hotmart_checkout_url }}?email={{ urlencode(auth()->user()->email) }}&utm_source=plataforma&utm_medium=checkout"
                               target="_blank"
                               class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m.6 8L6 21h12"></path>
                                </svg>
                                Comprar
                            </a>
                        @else
                            <span class="inline-flex items-center px-6 py-3 bg-gray-300 text-gray-500 font-medium rounded-lg cursor-not-allowed">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 6.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                Indisponível
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Resumo do pedido -->
        <div class="bg-gray-50 rounded-lg p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Resumo do pedido</h3>
            <div class="space-y-2">
                @foreach($agents as $agent)
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ $agent->name }}</span>
                        <span class="font-medium">R$ {{ number_format($agent->price, 2, ',', '.') }}</span>
                    </div>
                @endforeach
                <div class="border-t pt-2 mt-4">
                    <div class="flex justify-between text-lg font-bold">
                        <span>Total</span>
                        <span class="text-green-600">R$ {{ number_format($total, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações importantes -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
            <div class="flex">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h4 class="text-blue-900 font-medium mb-1">Informações importantes:</h4>
                    <ul class="text-blue-800 text-sm space-y-1">
                        <li>• Você será redirecionado para o checkout seguro da Hotmart</li>
                        <li>• Seu email será preenchido automaticamente</li>
                        <li>• O acesso será liberado automaticamente após a confirmação do pagamento</li>
                        <li>• Você receberá um email de confirmação com os detalhes da compra</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Botões de navegação -->
        <div class="flex justify-between items-center">
            <a href="{{ route('cart.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Voltar ao carrinho
            </a>

            <a href="{{ route('agents.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                Ver mais agentes
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>
</x-app-layout>
