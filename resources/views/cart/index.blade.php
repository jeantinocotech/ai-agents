<x-app-layout>
    <div class="container mx-auto py-12 px-2 md:px-0">
        <h1 class="cart-title">Seu Carrinho</h1>

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
            <div class="cart-box">
                @foreach ($cart as $agent)
                    @php
                        $total += $agent['price'];
                    @endphp
                    
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <h2 class="cart-item-title">{{ $agent['name'] }}</h2>
                            <p class="cart-item-desc">{{ $agent['description'] }}</p>
                        </div>
                        <div class="cart-item-actions">
                            <span class="cart-item-title">
                                R$ {{ number_format($agent['price'], 2, ',', '.') }}
                            </span>
                            <form method="POST" action="{{ route('cart.remove', $agent['id']) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="cart-remove-btn"
                                        onclick="return confirm('Tem certeza que deseja remover este agente do carrinho?')">
                                        &#10005; <!-- X -->
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="cart-box">
                <div class="cart-total-row">
                    <span class="cart-total-label">Total:</span>
                    <span class="cart-item-title">
                        R$ {{ number_format($total, 2, ',', '.') }}
                    </span>
                </div>

                <div class="cart-actions">
                    <form method="POST" action="{{ route('cart.clear') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-outline"
                                onclick="return confirm('Tem certeza que deseja limpar todo o carrinho?')">
                            Limpar
                        </button>
                    </form>

                    <a href="{{ route('cart.checkout') }}" class="btn btn-primary">
                        Finalizar
                    </a>
                </div>
            </div>

        @else
            <div class="cart-box text-center">
                <div class="text-gray-400 mb-4">
                    <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m.6 8L6 21h12M9 19a2 2 0 100 4 2 2 0 000-4zm8 0a2 2 0 100 4 2 2 0 000-4z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium mb-2" style="color: #222;">Seu carrinho está vazio</h3>
                <p class="mb-6" style="color: #666;">Adicione alguns agentes ao seu carrinho para continuar.</p>
                <a href="{{ route('agents.index') }}" class="btn btn-primary">
                    Ver Agentes
                </a>
            </div>
        @endif

    </div>
</x-app-layout>
