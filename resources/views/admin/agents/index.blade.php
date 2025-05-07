<!-- resources/views/admin/agents/index.blade.php -->
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Gerenciar Agentes</h2>
                        <a href="{{ route('admin.agents.create') }}" 
                           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                            Adicionar Novo Agente
                        </a>
                    </div>
                    
                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif
                    
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID
                                </th>
                                <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Imagem
                                </th>
                                <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nome
                                </th>
                                <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tipo de Modelo
                                </th>
                                <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Data de Criação
                                </th>
                                <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($agents as $agent)
                                <tr>
                                    <td class="py-4 px-4 border-b border-gray-200">
                                        {{ $agent->id }}
                                    </td>
                                    <td class="py-4 px-4 border-b border-gray-200">
                                        <img src="{{ asset('storage/' . $agent->image_path) }}" 
                                             alt="{{ $agent->name }}" 
                                             class="w-16 h-16 object-cover rounded">
                                    </td>
                                    <td class="py-4 px-4 border-b border-gray-200">
                                        {{ $agent->name }}
                                    </td>
                                    <td class="py-4 px-4 border-b border-gray-200">
                                        {{ $agent->model_type }}
                                    </td>
                                    <td class="py-4 px-4 border-b border-gray-200">
                                        {{ $agent->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="py-4 px-4 border-b border-gray-200">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('admin.agents.edit', $agent->id) }}" 
                                               class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                                Editar
                                            </a>
                                            <form action="{{ route('admin.agents.destroy', $agent->id) }}" method="POST" 
                                                  onsubmit="return confirm('Tem certeza que deseja excluir este agente?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                               
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 px-4 border-b border-gray-200 text-center">
                                        Nenhum agente encontrado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>