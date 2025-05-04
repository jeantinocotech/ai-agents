// resources/views/cart/checkout.blade.php
<!-- resources/views/cart/checkout.blade.php -->

<x-app-layout>
    <div class="max-w-4xl mx-auto py-12">
        <h1 class="text-3xl font-bold mb-8">Seu Carrinho</h1>

        @if(count($cart) > 0)
            <div class="space-y-6">
                @foreach($cart as $item)
                    <div class="p-4 border rounded shadow flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-semibold">{{ $item['name'] }}</h2> <!-- Se for um array -->
                            <p class="text-gray-600">{{ $item['description'] }}</p>
                        </div>
                        <div class="text-lg font-bold">
                            R$ {{ number_format($item['price'], 2, ',', '.') }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex justify-between items-center">
                <div class="text-2xl font-bold">
                    Total: R$ {{ number_format($total, 2, ',', '.') }}
                </div>

                <form method="POST" action="{{ route('cart.processCheckout') }}">
                    @csrf
                    <button class="px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        Finalizar Compra
                    </button>
                </form>
            </div>
        @else
            <p class="text-gray-500">Seu carrinho está vazio.</p>
        @endif
    </div>
</x-app-layout>
