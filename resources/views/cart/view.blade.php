<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h2 class="text-2xl font-semibold mb-6">Meu Carrinho</h2>

                @if(session('cart') && count(session('cart')) > 0)
                    <div class="space-y-4">
                        @foreach(session('cart') as $id => $details)
                            <div class="p-4 border rounded flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-bold">{{ $details['name'] }}</h3>
                                    <p>Preço: R$ {{ number_format($details['price'], 2, ',', '.') }}</p>
                                </div>
                                <form action="{{ route('cart.remove', $id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">
                                        Remover
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        <a href="{{ route('checkout') }}" class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded">
                            Finalizar Compra
                        </a>
                    </div>
                @else
                    <p>Seu carrinho está vazio.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
