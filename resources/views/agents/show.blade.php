<!-- resources/views/agents/show.blade.php -->
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center mb-6">
                        <a href="{{ route('home') }}" class="text-blue-500 hover:underline mr-4">
                            &larr; Voltar para página inicial
                        </a>
                        <h2 class="text-2xl font-bold">{{ $agent->name }}</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Imagem e vídeo -->
                        <div>
                            <div class="mb-6">
                                <img src="{{ asset('storage/' . $agent->image_path) }}" alt="{{ $agent->name }}" 
                                     class="w-full h-auto rounded-lg">
                            </div>
                            
                            <div>
                                <video controls class="w-full h-auto rounded-lg">
                                    <source src="{{ asset('storage/' . $agent->video_path) }}" type="video/mp4">
                                    Seu navegador não suporta vídeos HTML5.
                                </video>
                            </div>
                        </div>
                        
                        <!-- Descrição e informações -->
                        <div>
                            <div class="bg-gray-100 p-6 rounded-lg mb-6">
                                <h3 class="text-xl font-bold mb-4">Sobre este assistente</h3>
                                <p class="text-gray-700">{{ $agent->description }}</p>
                            </div>
                            
                            <div class="bg-gray-100 p-6 rounded-lg mb-6">
                                <h3 class="text-xl font-bold mb-4">Tipo de modelo</h3>
                                <p class="text-gray-700">{{ $agent->model_type }}</p>
                            </div>
                            
                            <div class="mt-6">
                                <a href="{{ route('agents.chat', $agent->id) }}" 
                                   class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg inline-block">
                                    Iniciar conversa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>