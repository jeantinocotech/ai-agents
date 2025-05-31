<x-app-layout>
    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Seu Carrinho</h1>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                {{ session('success') }}
            </div>
        @endif


       @php
            $total = 0;
        @endphp

        @foreach ($cart as $agent)
            
            @php
                $total += $agent['price'];
            @endphp
            <div class="flex justify-between items-center p-4 border-b">
                <div>
                    <h2 class="text-lg font-semibold"> {{ $agent['name'] }} </h2>
                </div>
                <div class="flex items-center">
                    <span class="text-xl font-bold mr-4">R$ {{ number_format($agent['price'], 2, ',', '.') }}</span>
                    <form method="POST" action="{{ route('cart.remove', $agent['id']) }}">
                        @csrf
                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                            Remover
                        </button>
                    </form>
                </div>
            </div>
        @endforeach

        @if (count($cart) > 0)
            <div class="flex justify-end items-center p-4 border-t font-bold text-xl">
                Total: R$ {{ number_format($total, 2, ',', '.') }}
            </div>

            <div class="flex justify-end mt-6">
                <form method="POST" action="{{ route('cart.processCheckout') }}">
                    @csrf
                    <button type="submit" class="bg-blue-500 text-white px-6 py-3 rounded hover:bg-blue-600">
                        Finalizar Compra
                    </button>
                </form>
            </div>

        @else
            <p class="text-gray-600">Seu carrinho está vazio.</p>
        @endif

    </div>
</x-app-layout>
