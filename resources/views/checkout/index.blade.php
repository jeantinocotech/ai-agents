<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-6">Checkout</h2>

                @if(session('message'))
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" role="alert">
                        {{ session('message') }}
                    </div>
                @endif

                @php
                    $cart = session('cart', []);
                    $total = 0;
                @endphp

                @if(count($cart) > 0)
                    <table class="w-full text-left mb-6">
                        <thead>
                            <tr>
                                <th class="py-2">Agente</th>
                                <th class="py-2">Preço</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cart as $item)
                                <tr>
                                    <td class="py-2">{{ $item['name'] }}</td>
                                    <td class="py-2">R$ {{ number_format($item['price'], 2, ',', '.') }}</td>
                                </tr>
                                @php
                                    $total += $item['price'];
                                @endphp
                            @endforeach
                        </tbody>
                    </table>

                    <div class="text-right text-xl font-semibold mb-6">
                        Total: R$ {{ number_format($total, 2, ',', '.') }}
                    </div>

                    <form action="#" method="POST">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Finalizar Compra
                        </button>
                    </form>
                @else
                    <p>Seu carrinho está vazio.</p>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
