<!-- resources/views/admin/agents/edit.blade.php -->
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center mb-6">
                        <a href="{{ route('admin.agents.index') }}" class="text-blue-500 hover:underline mr-4">
                            &larr; Voltar para lista
                        </a>
                        <h2 class="text-2xl font-bold">Editar Agente: {{ $agent->name }}</h2>
                    </div>
                    
                    @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <strong class="font-bold">Atenção!</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form action="{{ route('admin.agents.update', $agent->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nome do Agente</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $agent->name) }}" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Descrição</label>
                            <textarea name="description" id="description" rows="4" 
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('description', $agent->description) }}</textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Imagem Atual</label>
                            <img src="{{ asset('storage/' . $agent->image_path) }}" alt="{{ $agent->name }}" 
                                 class="w-32 h-32 object-cover rounded mb-2">
                            
                            <label for="image" class="block text-gray-700 text-sm font-bold mb-2">Nova Imagem (opcional)</label>
                            <input type="file" name="image" id="image" accept="image/*"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <p class="text-sm text-gray-500 mt-1">Formatos aceitos: JPG, PNG, GIF (max 2MB)</p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Vídeo Atual do YouTube</label>
                            <div class="aspect-w-16 aspect-h-9 mb-2">
                                <iframe 
                                    src="{{ $agent->youtube_url }}" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen
                                    class="w-full h-48"
                                ></iframe>
                            </div>
                            
                            <label for="youtube_video_id" class="block text-gray-700 text-sm font-bold mb-2">Novo ID do Vídeo no YouTube</label>
                            <input type="text" name="youtube_video_id" id="youtube_video_id" 
                                   value="{{ old('youtube_video_id', $agent->youtube_video_id) }}" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <p class="text-sm text-gray-500 mt-1">Ex: "dQw4w9WgXcQ" da URL https://www.youtube.com/watch?v=dQw4w9WgXcQ</p>
                        </div>
                        
                        <div class="mb-4">
                            <label for="api_key" class="block text-gray-700 text-sm font-bold mb-2">Chave API (opcional)</label>
                            <input type="text" name="api_key" id="api_key" value="{{ old('api_key', $agent->api_key) }}" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        
                        <div class="mb-6">
                            <label for="model_type" class="block text-gray-700 text-sm font-bold mb-2">Tipo de Modelo</label>
                            <select name="model_type" id="model_type" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Selecione um modelo</option>
                                <option value="GPT-3.5" {{ old('model_type', $agent->model_type) == 'GPT-3.5' ? 'selected' : '' }}>GPT-3.5</option>
                                <option value="GPT-4" {{ old('model_type', $agent->model_type) == 'GPT-4' ? 'selected' : '' }}>GPT-4</option>
                                <option value="Claude" {{ old('model_type', $agent->model_type) == 'Claude' ? 'selected' : '' }}>Claude</option>
                                <option value="Custom" {{ old('model_type', $agent->model_type) == 'Custom' ? 'selected' : '' }}>Modelo Personalizado</option>
                            </select>
                        </div>
                        
                        <div class="flex items-center justify-end">
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Atualizar Agente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>