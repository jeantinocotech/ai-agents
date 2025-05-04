<x-app-layout>
    <div class="max-w-4xl mx-auto p-6">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h1 class="text-2xl font-bold mb-4">Compra realizada com sucesso!</h1>
            <p class="mb-6 text-gray-700">Obrigado por adquirir nossos agentes. Seu acesso será liberado em breve!</p>
            
            <div class="flex space-x-4">
                <a href="{{ route('agents.index') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Voltar para os Agentes</a>
                <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Ir para o Dashboard</a>
            </div>
        </div>
    </div>
</x-app-layout>
