<x-app-layout>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-200 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-bold mb-6">Nossos Assistentes de IA</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($agents as $agent)

                        @if($agent->is_active)

                            @php
                                $jaPossui = in_array($agent->id, $purchasedAgentIds ?? []);
                            @endphp

                            <div class="bg-gray-100 rounded-lg overflow-hidden shadow-md relative">
                                
                                @if($jaPossui)
                                    <div class="absolute top-2 right-2 bg-green-600 text-white text-xs px-2 py-1 rounded">
                                        ✅ assinado
                                    </div>
                                @endif

                                <!-- Imagem -->
                                <div class="h-48 bg-gray-200">
                                    <img src="{{ asset('storage/' . $agent->image_path) }}" alt="{{ $agent->name }}" 
                                        class="w-full h-full object-cover">
                                </div>
                                
                                <div class="p-4">
                                    <h3 class="text-xl font-bold mb-2">{{ $agent->name }}</h3>
                                    <!-- Estrelas -->
                                    @php
                                        $media = round($agent->ratings_avg_rating ?? 0, 1);
                                    @endphp

                                    <div class="flex items-center mb-2">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <= floor($media))
                                                <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                                    <polygon points="10,1 12,7 18,7 13,11 15,17 10,13 5,17 7,11 2,7 8,7" />
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4 text-gray-300 fill-current" viewBox="0 0 20 20">
                                                    <polygon points="10,1 12,7 18,7 13,11 15,17 10,13 5,17 7,11 2,7 8,7" />
                                                </svg>
                                            @endif
                                        @endfor
                                        <span class="ml-2 text-sm text-gray-600">({{ $media }}/5)</span>
                                    </div>

                                    <p class="text-gray-700 mb-4">{{ $agent->description }}</p>

                                    @if(!$jaPossui)
                                        <div class="text-lg font-semibold mb-4">R$ {{ number_format($agent->price, 2, ',', '.') }}</div>
                                    @else
                                        <div class="text-gray-600 text-decoration: line-through mb-4">R$ {{ number_format($agent->price, 2, ',', '.') }} </div>
                                    @endif    

                                    @if(!$jaPossui)
                                        <form method="POST" action="{{ route('agents.addToCart', $agent->id) }}">
                                            @csrf
                                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 mb-4">
                                                Adicionar ao Carrinho
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <!-- YouTube -->
                                <div class="p-4 border-t border-gray-200">
                                    <div class="aspect-w-16 aspect-h-9">
                                        <iframe 
                                            src="{{ $agent->youtube_url }}" 
                                            frameborder="0" 
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                            allowfullscreen
                                            class="w-full h-48"
                                        ></iframe>
                                    </div>
                                </div>
                            </div>

                        @endif

                    @endforeach

                    </div>
                    
                    @guest
                        <div class="mt-8 text-center">
                            <p class="mb-4">Para acessar todos os assistentes, crie uma conta ou faça login.</p>
                            <div class="flex justify-center gap-4">
                                <a href="{{ route('register') }}" 
                                   class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                                    Criar conta
                                </a>
                                <a href="{{ route('login') }}" 
                                   class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                                    Fazer login
                                </a>
                            </div>
                        </div>
                    @endguest
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
