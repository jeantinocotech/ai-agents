<x-app-layout>

<!-- HERO / CHAMADA PRINCIPAL -->
<section class="bg-[#1a1c1e] py-20">
    <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between">
        <div class="md:w-2/3 text-white">
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4">Potencialize suas atividades com nossos assistentes de AI</h1>
            <p class="text-xl text-gray-300 mb-8">Aumente suas chances de conseguir uma entrevista de emprego identificando as palavras-chave e passando pelo filtro do ATS.</p>
            <a href="#agentes" class="inline-block px-8 py-3 rounded-full bg-white text-black font-semibold shadow hover:bg-gray-200 transition">Ver Agentes</a>
        </div>
        <div class="md:w-1/3 mt-10 md:mt-0 flex justify-center">
            <img src="https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=400&q=80" alt="IA" class="rounded-xl shadow-xl w-full max-w-xs object-cover grayscale" />
        </div>
    </div>
</section>

<div class="py-12 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-200 text-green-800 rounded">
                {{ session('success') }}
            </div>
        @endif

        <h2 id="agentes" class="text-2xl font-bold mb-8 text-gray-900 text-center">Nossos Assistentes</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach ($agents as $agent)
                @if($agent->is_active)
                    @php
                        $jaPossui = in_array($agent->id, $purchasedAgentIds ?? []);
                        $media = round($agent->ratings_avg_rating ?? 0, 1);
                    @endphp

                    <div class="bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition relative flex flex-col h-full">
                        @if($jaPossui)
                            <div class="absolute top-2 right-2 bg-green-600 text-white text-xs px-2 py-1 rounded shadow">✅ Assinado</div>
                        @endif

                        <!-- Imagem -->
                        <div class="h-48 bg-gray-200 flex items-center justify-center">
                            <img src="{{ asset('storage/' . $agent->image_path) }}" alt="{{ $agent->name }}" class="w-full h-full object-cover">
                        </div>

                        <div class="p-6 flex-1 flex flex-col">
                            <h3 class="text-xl font-bold mb-1 text-gray-900">{{ $agent->name }}</h3>
                            <!-- Estrelas -->
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
                                <div class="text-gray-400 line-through mb-4">R$ {{ number_format($agent->price, 2, ',', '.') }}</div>
                            @endif

                            @if(!$jaPossui)
                                <form method="POST" action="{{ route('agents.addToCart', $agent->id) }}" class="mt-auto">
                                    @csrf
                                    <button type="submit" class="w-full px-4 py-2 bg-black text-white rounded hover:bg-gray-800 transition mb-2">
                                        Adicionar ao Carrinho
                                    </button>
                                </form>
                            @endif
                        </div>

                        <!-- YouTube -->
                        @if($agent->youtube_url)
                        <div class="p-4 border-t border-gray-200 bg-gray-50">
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
                        @endif
                    </div>
                @endif
            @endforeach
        </div>

        @guest
            <div class="mt-12 text-center">
                <p class="mb-4 text-gray-700">Para acessar todos os assistentes, crie uma conta ou faça login.</p>
                <div class="flex justify-center gap-4">
                    <a href="{{ route('register') }}" class="bg-black hover:bg-gray-800 text-white px-4 py-2 rounded">
                        Criar conta
                    </a>
                    <a href="{{ route('login') }}" class="bg-gray-800 hover:bg-black text-white px-4 py-2 rounded">
                        Fazer login
                    </a>
                </div>
            </div>
        @endguest

        <!-- Call to Action no final -->
        <div class="mt-20 text-center">
            <h2 class="text-2xl font-extrabold text-gray-900 mb-4">Desbloqueie todo o potencial da AI</h2>
            <p class="text-gray-600 mb-6">Assine agora e tenha acesso imediato aos melhores agentes do mercado!</p>
            <a href="#agentes" class="inline-block px-8 py-3 rounded-full bg-black text-white font-semibold shadow hover:bg-gray-800 transition">
                Ver Planos de Assinatura
            </a>
        </div>

        <!-- Depoimentos -->
        @if($testimonials->count())
            <section class="py-16 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl font-extrabold text-gray-900">O que dizem nossos usuários</h2>
                        <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-500">
                            Experiências reais de quem já usa GratoAI!
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        @foreach($testimonials as $testimonial)
                            <div class="bg-gray-50 rounded-lg p-6 shadow-sm">
                                <div class="flex items-center mb-4">
                                    @php
                                        // Detecta se é avatar ou foto de perfil (armazenada em storage)
                                        $img = '';
                                        if ($testimonial->author_image) {
                                            $img = asset($testimonial->author_image);
                                        } else {
                                            $img = 'https://ui-avatars.com/api/?name=' . urlencode($testimonial->author_name) . '&background=23272a&color=fff&size=128';
                                        }
                                    @endphp
                                    <img class="h-12 w-12 rounded-full object-cover" src="{{ $img }}" alt="{{ $testimonial->author_name }}">
                                    <div class="ml-4">
                                        <h4 class="text-lg font-medium text-gray-900">{{ $testimonial->author_name ?? 'Usuário GratoAI' }}</h4>
                                        @if($testimonial->author_role)
                                            <p class="text-gray-600 text-sm">{{ $testimonial->author_role }}</p>
                                        @endif
                                    </div>
                                </div>
                                <p class="text-gray-700">
                                    “{{ $testimonial->content }}”
                                </p>
                                @if($testimonial->agent && $testimonial->agent->name)
                                    <p class="mt-2 text-xs text-gray-400">
                                        (Sobre o agente: <span class="font-semibold">{{ $testimonial->agent->name }}</span>)
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif


        <footer class="bg-black text-gray-300 mt-20">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Logo e descrição -->
            <div>
                <div class="flex items-center mb-2">
                <img src="{{ asset('img/gratoai_black.png') }}" alt="GratoAI" class="h-10 w-auto mr-2">
                <span class="text-xl font-bold text-white">GratoAI</span>
                </div>
                <p class="text-gray-400 mt-2">IA para apoiar, facilitar e transformar sua rotina. A gratidão da tecnologia ao seu lado.</p>
            </div>
            <!-- Links rápidos -->
            <div>
                <h3 class="text-lg font-semibold text-white mb-4">Links</h3>
                <ul class="space-y-2">
                <li><a href="#agentes" class="hover:text-white">Agentes</a></li>
                <li><a href="{{ route('register') }}" class="hover:text-white">Cadastrar</a></li>
                <li><a href="{{ route('login') }}" class="hover:text-white">Entrar</a></li>
                </ul>
            </div>
            <!-- Contato -->
            <div>
                <h3 class="text-lg font-semibold text-white mb-4">Contato</h3>
                <ul class="space-y-2 text-gray-400">
                <li>
                    <span class="inline-block w-5"><i class="fas fa-envelope"></i></span>
                    <a href="mailto:contato@gratoai.com" class="hover:text-white">contato@gratoai.com</a>
                </li>
                </ul>
            </div>
            <!-- Siga-nos -->
            <div>
                <h3 class="text-lg font-semibold text-white mb-4">Siga-nos</h3>
                <div class="flex space-x-4 mt-2">
                <a href="#" class="hover:text-white text-2xl"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="hover:text-white text-2xl"><i class="fab fa-instagram"></i></a>
                <a href="#" class="hover:text-white text-2xl"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            </div>
            <div class="mt-10 border-t border-gray-800 pt-6 flex flex-col md:flex-row justify-between items-center">
            <p class="text-gray-500">&copy; {{ date('Y') }} GratoAI. Todos os direitos reservados.</p>
            <div class="flex space-x-6 mt-2 md:mt-0">
                <a href="{{ route('termos-uso') }}" class="text-gray-400 hover:text-white">Termos de uso</a>
                <a href="{{ route('privacidade') }}"  class="text-gray-400 hover:text-white">Política de Privacidade</a>
            </div>
            </div>
        </div>
        </footer>
    </div>
</div>
</x-app-layout>



