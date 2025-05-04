<!-- resources/views/dashboard.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-bold mb-6">Meus Assistentes de IA</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($agents as $agent)
                            <div class="bg-gray-100 rounded-lg overflow-hidden shadow-md">
                                <!-- Imagem do agente -->
                                <div class="h-48 bg-gray-200">
                                    <img src="{{ asset('storage/' . $agent->image_path) }}" alt="{{ $agent->name }}" 
                                        class="w-full h-full object-cover">
                                </div>
                                
                                <!-- Descritivo -->
                                <div class="p-4">
                                    <h3 class="text-xl font-bold mb-2">{{ $agent->name }}</h3>
                                    <p class="text-gray-700 mb-4">{{ Str::limit($agent->description, 100) }}</p>
                                    
                                    <div class="flex space-x-2">
                                        <a href="{{ route('agents.show', $agent->id) }}" 
                                           class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                                            Detalhes
                                        </a>
                                        <a href="{{ route('agents.chat', $agent->id) }}" 
                                           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                                            Iniciar Chat
                                        </a>
                                        @if (auth()->user()->isAdmin())
                                            <a href="{{ route('admin.dashboard') }}" class="bg-indigo-500 text-white px-4 py-2 rounded">
                                                Administração
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
