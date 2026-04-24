<x-app-layout>
    <div class="max-w-4xl mx-auto py-12 px-6">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Checkout</h1>
            <p class="text-gray-600">
                O pagamento de assinaturas por agente foi descontinuado. Use <strong>tokens</strong> para conversar com os agentes e compre pacotes pelo checkout integrado (Asaas).
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
                        @auth
                            <a href="{{ route('tokens.purchase') }}"
                               class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                                Comprar tokens
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                Entrar para comprar tokens
                            </a>
                        @endauth
                    </div>
                </div>
            @endforeach
        </div>

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
                        <span>Total (referência legado)</span>
                        <span class="text-green-600">R$ {{ number_format($total, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
            <div class="flex">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h4 class="text-blue-900 font-medium mb-1">Pagamentos</h4>
                    <ul class="text-blue-800 text-sm space-y-1">
                        <li>• Pacotes de tokens são pagos via Asaas (PIX, boleto ou cartão)</li>
                        <li>• O saldo de tokens é creditado após confirmação do pagamento</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('cart.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Voltar ao carrinho
            </a>

            <a href="{{ route('home') }}" class="inline-flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                Página inicial
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>
</x-app-layout>
