<!-- resources/views/checkout.blade.php -->
<x-app-layout>
    <div class="max-w-4xl mx-auto py-12">
        <h1 class="text-3xl font-bold mb-8">Finalize sua compra</h1>

        <p class="mb-6 text-gray-600">Clique no botão abaixo para comprar cada agente individualmente pela Hotmart.</p>

        @foreach($agents as $agent)
            <div class="p-4 border rounded shadow mb-4 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold">{{ $agent->name }}</h2>
                    <p class="text-gray-600">{{ $agent->description }}</p>
                </div>

                @if($agent->hotmart_checkout_url)
                    <a href="{{ $agent->hotmart_checkout_url }}?email={{ urlencode(auth()->user()->email) }}"
                       target="_blank"
                       class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                       Comprar
                    </a>
                @else
                    <span class="text-red-500">Link não disponível</span>
                @endif
            </div>
        @endforeach

        <a href="{{ route('home') }}" class="inline-block mt-6 text-blue-600 hover:underline">
            ← Voltar à página inicial
        </a>
    </div>
</x-app-layout>
