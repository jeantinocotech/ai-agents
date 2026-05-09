<nav x-data="{ open: false }" class="sticky top-0 z-50 bg-black shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo / marca (sempre leva ao início) -->
            <a href="{{ url('/') }}" aria-label="GratoAI — início" class="flex items-center shrink-0 space-x-3 rounded-md text-white hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-black focus-visible:ring-white">
                <img src="{{ asset(config('branding.logo_main')) }}" alt="" class="pointer-events-none h-12 w-auto" width="140" height="48">
                <span class="font-bold text-xl">GratoAI (Versão Beta)</span>
            </a>

            <!-- Desktop Nav -->
            <div class="hidden sm:flex sm:items-center space-x-8">
                @guest
                    <x-nav-link :href="url('/#trilha-teaser')" :active="false">
                        A trilha
                    </x-nav-link>
                @endguest

                @auth
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-nav-link>
                    <x-nav-link :href="route('career-trail.index')" :active="request()->routeIs('career-trail.*')">
                        Trilha
                    </x-nav-link>
                @endauth

                <!-- Administração (apenas para admin) -->
                @auth
                    @if(Auth::user()->isAdmin())
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
                                <x-dropdown-link :href="route('admin.testimonials.index')">
                                    📝 Aprovar Depoimentos
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.settings.tokens.edit')">
                                    ⚙️ Parâmetros de tokens
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.gamification.index')">
                                    🏅 Parâmetros de gamificação
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.career-trail-steps.index')">
                                    🪶 Trilha — passos
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.career-trail-graca-messages.index')">
                                    🪶 Mensagens da Graça (trilha)
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @endif
                @endauth
            </div>

            <!-- User Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:gap-x-2">
                @auth
                    <x-gamification-bell />
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-300 bg-[#23272a] hover:text-white focus:outline-none transition">
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="ms-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <div class="border-b border-gray-100 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Área pessoal
                            </div>
                            <x-dropdown-link :href="route('dashboard')">
                                Dashboard
                            </x-dropdown-link>
                            @php
                                $snap = \App\Models\UserGamificationSnapshot::query()
                                    ->with('rank')
                                    ->where('user_id', Auth::id())
                                    ->first();
                            @endphp
                            @if ($snap)
                                <div class="px-4 py-2 text-sm text-slate-700">
                                    <span class="text-slate-500">Rank:</span>
                                    @if (! empty($snap->rank?->icon_key))
                                        <span class="me-0.5" aria-hidden="true">{{ $snap->rank->icon_key }}</span>
                                    @endif
                                    <strong>{{ $snap->rank?->title ?? '—' }}</strong>
                                    <span class="text-slate-400">·</span>
                                    <strong class="tabular-nums">{{ number_format((int) $snap->score_total, 0, ',', '.') }}</strong>
                                    <span class="text-slate-500">pts</span>
                                </div>
                                <div class="my-1 border-t border-gray-100"></div>
                            @endif
                            <x-dropdown-link :href="route('tokens.purchase')">
                                Comprar tokens
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('tokens.history')">
                                Histórico de tokens
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('testimonials.mine')">
                                Meus depoimentos
                            </x-dropdown-link>
                            <div class="my-1 border-t border-gray-100"></div>
                            <x-dropdown-link :href="route('profile.edit')">
                                Perfil da conta
                            </x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    Sair da sessão
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
        @auth
            <div class="px-4 pt-4">
                <div class="flex items-center justify-between rounded-xl border border-white/10 bg-black/20 px-3 py-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Tokens</p>
                        <p class="mt-0.5 text-sm text-gray-100">
                            Saldo:
                            <strong class="tabular-nums">{{ number_format((int) Auth::user()->token_balance, 0, ',', '.') }}</strong>
                        </p>
                    </div>
                    <a href="{{ route('tokens.purchase') }}"
                       class="shrink-0 inline-flex items-center rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-black hover:bg-teal-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300 focus-visible:ring-offset-2 focus-visible:ring-offset-[#23272a]">
                        Comprar
                    </a>
                </div>
            </div>
            <div class="sm:hidden px-4 pt-3">
                <x-gamification-bell :compact="true" />
            </div>
        @endauth
        <div class="pt-2 pb-3 space-y-1">
            @guest
                <x-responsive-nav-link :href="url('/#trilha-teaser')" :active="false">
                    A trilha
                </x-responsive-nav-link>
            @endguest
            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    Dashboard
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('career-trail.index')" :active="request()->routeIs('career-trail.*')">
                    Trilha
                </x-responsive-nav-link>
                @if(Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        📊 Administração
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.agents.index')" :active="request()->routeIs('admin.agents.*')">
                        🤖 Gerenciar Agentes
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.gamification.index')" :active="request()->routeIs('admin.gamification.*')">
                        🏅 Parâmetros de gamificação
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.career-trail-steps.index')" :active="request()->routeIs('admin.career-trail-steps.*')">
                        🪶 Trilha — passos
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.career-trail-graca-messages.index')" :active="request()->routeIs('admin.career-trail-graca-messages.*')">
                        🪶 Mensagens da Graça
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
                    <p class="px-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Área pessoal</p>
                    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('tokens.purchase')" :active="request()->routeIs('tokens.purchase')">
                        Comprar tokens
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('tokens.history')" :active="request()->routeIs('tokens.history')">
                        Histórico de tokens
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('testimonials.mine')" :active="request()->routeIs('testimonials.mine')">
                        Meus depoimentos
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('profile.edit')">
                        Perfil da conta
                    </x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            Sair da sessão
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
