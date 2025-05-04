<x-app-layout>
    <div class="p-4">
        <h2 class="text-2xl font-bold mb-4">Painel de Administração</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 border rounded shadow">
                <p class="text-lg font-semibold">Total de Agentes: {{ $totalAgents }}</p>
            </div>

            <div class="p-4 border rounded shadow">
                <a href="{{ route('admin.agents.index') }}" class="bg-blue-500 text-white px-4 py-2 rounded block text-center">
                    Gerenciar Agentes
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
