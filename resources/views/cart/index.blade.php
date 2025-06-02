<<x-app-layout>
    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Seu Carrinho</h1>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
                {{ session('error') }}
            </div>
        @endif

        @if (session('info'))
            <div class="mb-4 p-4 bg-blue-100 text-blue-800 rounded">
                {{ session('info') }}
            </div>
        @endif

        @php
            $total = 0;
        @endphp

        @if (count($cart) > 0)
            @foreach ($cart as $agent)
                @php
                    $total += $agent['price'];
                @endphp
                <div class="flex justify-between items-center p-4 border-b bg-white rounded-lg mb-4 shadow-sm">
                    <div class="flex-1">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $agent['name'] }}</h2>
                        <p class="text-gray-600 text-sm mt-1">{{ $agent['description'] }}</p>
                    </div>
                    <div class="flex items-center">
                        <span class="text-xl font-bold mr-4 text-green-600">
                            R$ {{ number_format($agent['price'], 2, ',', '.') }}
                        </span>
                        <form method="POST" action="{{ route('cart.remove', $agent['id']) }}">
                            @csrf
                            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition-colors">
                                Remover
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach

            <div class="bg-white rounded-lg shadow-sm p-4 mt-6">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-xl font-bold text-gray-900">Total:</span>
                    <span class="text-2xl font-bold text-green-600">
                        R$ {{ number_format($total, 2, ',', '.') }}
                    </span>
                </div>

                <div class="flex justify-between items-center">
                    <form method="POST" action="{{ route('cart.clear') }}" class="mr-4">
                        @csrf
                        <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors"
                                onclick="return confirm('Tem certeza que deseja limpar todo o carrinho?')">
                            Limpar Carrinho
                        </button>
                    </form>

                    <a href="{{ route('cart.checkout') }}" class="bg-blue-500 text-white px-6 py-3 rounded hover:bg-blue-600 transition-colors">
                        Finalizar Compra
                    </a>
                </div>
            </div>

        @else
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <div class="text-gray-400 mb-4">
                    <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m.6 8L6 21h12M9 19a2 2 0 100 4 2 2 0 000-4zm8 0a2 2 0 100 4 2 2 0 000-4z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Seu carrinho está vazio</h3>
                <p class="text-gray-600 mb-6">Adicione alguns agentes ao seu carrinho para continuar.</p>
                <a href="{{ route('agents.index') }}" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 transition-colors">
                    Ver Agentes
                </a>
            </div>
        @endif

    </div>
</x-app-layout>
