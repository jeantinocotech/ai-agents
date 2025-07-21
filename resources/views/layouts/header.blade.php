<header class="bg-black shadow">

    <div class="max-w-7xl mx-auto px-4 flex justify-between items-center h-16">
        <div class="flex items-center space-x-3">
            <span class="inline-block align-middle">
            <img src="{{ asset('img/gratoai_black.png') }}" alt="GratoAI" class="mx-auto h-16 w-auto">
            </span>
            <span class="font-bold text-xl text-white">Grato AI</span>
        </div>

        <div class="flex items-center space-x-4">
            <a href="{{ url('/') }}" class="text-gray-200 hover:text-white">Início</a>
            <a href="#agentes" class="text-gray-200 hover:text-white">Agentes</a>
            @if (Route::has('login'))
                @auth
                    <div x-data="{ open: false }" class="relative ml-3">
                        <button @click="open = !open" class="flex items-center text-sm font-medium text-gray-200 hover:text-white focus:outline-none">
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="ml-2 fill-current h-4 w-4" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        </button>
                        <div
                            x-show="open"
                            @click.away="open = false"
                            class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 z-50"
                            x-transition
                        >
                            <a href="{{ route('profile.edit') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Meu Perfil
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Sair
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-md text-white bg-gray-800 hover:bg-gray-700">Entrar</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="ml-2 px-4 py-2 rounded-md border border-white text-white hover:bg-gray-900">Cadastrar</a>
                    @endif
                @endauth
            @endif
        </div>
    </div>
</header>
