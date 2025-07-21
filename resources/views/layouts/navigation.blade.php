<nav x-data="{ open: false }" class="bg-black shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center space-x-3">
                <img src="{{ asset('img/gratoai_black.png') }}" alt="GratoAI" class="h-12 w-auto">
                <span class="font-bold text-xl text-white">GratoAI</span>
            </div>

            <!-- Desktop Nav -->
            <div class="hidden sm:flex sm:items-center space-x-8">
                <x-nav-link :href="url('/')" :active="request()->is('/')">
                    Início
                </x-nav-link>
                <x-nav-link :href="url('/#agentes')" :active="request()->is('#agentes')">
                    Agentes
                </x-nav-link>
                <x-nav-link :href="route('cart.index')" :active="request()->routeIs('cart.index')">
                    🛒 Carrinho
                    @if(session('cart_count', 0) > 0)
                        <span class="ml-1 bg-green-500 text-xs text-white rounded-full px-2">
                            {{ session('cart_count', 0) }}
                        </span>
                    @endif
                </x-nav-link>

                <!-- Sempre mostra Dashboard para usuário logado -->
                @auth
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-nav-link>
                    <x-nav-link :href="route('testimonials.mine')" :active="request()->routeIs('testimonials.mine')" class="font-semibold">
                        <i class="fas fa-comment-dots mr-1"></i> Meus Depoimentos
                    </x-nav-link>
                @endauth

                <!-- Administração (apenas para admin) -->
                @auth
                    @if(Auth::user()->is_admin)
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-1 py-2 border-b-2 border-transparent text-sm font-medium leading-5 text-yellow-400 hover:text-white hover:border-yellow-300 focus:outline-none transition">
                                    ⚙️ Administração
                                    <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.23 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('admin.dashboard')">
                                    📊 Dashboard Admin
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.agents.index')">
                                    🤖 Gerenciar Agentes
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @endif
                @endauth
            </div>

            <!-- User Dropdown -->
            <div class="hidden sm:flex sm:items-center">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-300 bg-[#23272a] hover:text-white focus:outline-none transition">
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="ms-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <x-nav-link :href="route('login')" :active="request()->routeIs('login')" class="font-semibold">
                        Entrar
                    </x-nav-link>
                    @if (Route::has('register'))
                        <x-nav-link :href="route('register')" :active="request()->routeIs('register')" class="font-semibold">
                            Cadastrar
                        </x-nav-link>
                    @endif
                @endauth
            </div>

            <!-- Hamburger (Mobile) -->
            <div class="flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-[#23272a]">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="url('/')" :active="request()->is('/')">
                Início
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="url('/#agentes')" :active="request()->is('#agentes')">
                Agentes
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('cart.index')" :active="request()->routeIs('cart.index')">
                🛒 Carrinho
            </x-responsive-nav-link>
            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    Dashboard
                </x-responsive-nav-link>
                @if(Auth::user()->is_admin)
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        📊 Administração
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.agents.index')" :active="request()->routeIs('admin.agents.*')">
                        🤖 Gerenciar Agentes
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        @auth
            <div class="pt-4 pb-1 border-t border-gray-600">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-200">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-400">{{ Auth::user()->email }}</div>
                </div>
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @else
            <div class="pt-4 pb-1 border-t border-gray-600">
                <x-responsive-nav-link :href="route('login')">
                    Entrar
                </x-responsive-nav-link>
                @if (Route::has('register'))
                    <x-responsive-nav-link :href="route('register')">
                        Cadastrar
                    </x-responsive-nav-link>
                @endif
            </div>
        @endauth
    </div>

</nav>
