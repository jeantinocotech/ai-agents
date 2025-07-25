<x-app-layout>
    <div class="max-w-xl mx-auto mt-16 bg-white rounded-xl shadow p-8 text-center">
        <h2 class="text-2xl font-bold mb-4 text-gray-900">Para finalizar a compra</h2>
        <p class="mb-6 text-gray-700">Você precisa estar logado para concluir o pagamento.</p>
        <div class="flex justify-center gap-4">
            <a href="{{ route('login') }}" class="bg-black text-white px-6 py-2 rounded-lg hover:bg-gray-800 transition">Fazer Login</a>
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-black transition">Criar Conta</a>
            @endif
        </div>
    </div>
</x-app-layout>
