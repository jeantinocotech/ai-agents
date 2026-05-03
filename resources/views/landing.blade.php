<x-app-layout>
    @auth
        {{-- Utilizador autenticado sem CV de perfil: orientação no passo 1 (a rota / redireciona para a trilha quando já existe CV). --}}
        <section class="border-b border-violet-200/80 bg-gradient-to-b from-violet-50 via-white to-slate-50 py-14 sm:py-20">
            <div class="mx-auto flex max-w-5xl flex-col gap-10 px-4 md:flex-row md:items-start md:gap-12">
                <div class="flex shrink-0 justify-center md:w-1/3">
                    <div class="relative">
                        <div class="absolute -inset-1 rounded-3xl bg-gradient-to-br from-violet-400/30 to-indigo-500/20 blur-sm"></div>
                        <img src="{{ asset(config('career_trail.mentor_avatar', 'img/graca-avatar.png')) }}"
                             alt="{{ config('career_trail.mentor_label', 'Sra. Graça') }}"
                             class="relative w-full max-w-xs rounded-3xl object-cover shadow-xl ring-4 ring-white" />
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    @php
                        $mentor = config('career_trail.mentor_label', 'Sra. Graça');
                        $landingAuthFallback = "Como é a primeira vez que nos cruzamos aqui, deixe-me apresentar-me: eu sou a orientadora da sua trilha de carreira na GratoAI. Vou te acompanhar passo a passo — do currículo a entrevistas e propostas — e ajudar você a entender o que fazer em cada etapa, junto com os assistentes de IA quando fizer sentido.\n\nVocê não precisa memorizar nada: volte a falar comigo no mapa da trilha sempre que quiser se reorientar.";
                    @endphp
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Bem-vindo</p>
                    <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                        Sou a {{ $mentor }}
                    </h1>
                    <div class="mt-5 space-y-3 text-base leading-relaxed text-slate-700 sm:text-lg">
                        <x-graca-slot
                            :placement="\App\Support\CareerTrailGracaSlots::LANDING_AUTH_INTRO"
                            :step="null"
                            tag="div"
                            paragraph-class="space-y-3 text-base leading-relaxed text-slate-700 sm:text-lg"
                            :fallback="$landingAuthFallback"
                        />
                    </div>
                    <h2 class="mt-8 text-lg font-semibold tracking-tight text-slate-900 sm:text-xl">
                        O primeiro passo é o seu CV — o que prefere fazer agora?
                    </h2>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        <li class="flex gap-2">
                            <span class="mt-0.5 font-bold text-violet-600" aria-hidden="true">•</span>
                            <span><strong class="text-slate-800">Já tem um CV</strong> e quer <strong>salvá-lo na plataforma</strong> para usar nas etapas seguintes? Use a área completa — cole o texto, revise e salve.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-0.5 font-bold text-violet-600" aria-hidden="true">•</span>
                            <span><strong class="text-slate-800">Já tem um CV</strong> e quer <strong>sugestões de melhoria</strong>, ou <strong>não tem</strong> e quer <strong>criar do zero</strong>? Fale com o assistente no CV Creator.</span>
                        </li>
                    </ul>
                    <p class="mt-4 text-xs text-slate-500">
                        Se já tem texto final para arquivo, prefira <strong>CV completo</strong>. Se quer conversar com o assistente para estruturar ou refinar, prefira <strong>CV Creator</strong>.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a href="{{ route('career-trail.cv') }}"
                           class="inline-flex items-center justify-center rounded-xl bg-violet-600 px-8 py-3 text-center text-sm font-semibold text-white shadow-md transition hover:bg-violet-700">
                            CV completo
                        </a>
                        @if ($cvCreatorChatUrl ?? null)
                            <a href="{{ $cvCreatorChatUrl }}"
                               class="inline-flex items-center justify-center rounded-xl border-2 border-violet-600 bg-white px-8 py-3 text-center text-sm font-semibold text-violet-900 shadow-sm transition hover:bg-violet-50">
                                CV Creator
                            </a>
                        @endif
                    </div>
                    <p class="mt-6 text-sm text-slate-600">
                        <a href="{{ route('career-trail.index') }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">Ver o mapa da trilha</a>
                        <span class="text-slate-400"> — ou explore as etapas abaixo.</span>
                    </p>
                </div>
            </div>
        </section>
    @else
        <section class="bg-[#1a1c1e] py-20">
            <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-10">
                <div class="md:w-2/3 text-white">
                    <h1 class="text-4xl md:text-5xl font-extrabold mb-4">A sua trilha de carreira, com apoio de IA</h1>
                    <x-graca-slot
                        :placement="\App\Support\CareerTrailGracaSlots::LANDING_GUEST_HERO"
                        :step="null"
                        tag="div"
                        paragraph-class="text-xl text-gray-300 mb-8"
                        :fallback="'Deixe o CV passar no ATS, prepare entrevistas e negocie propostas — passo a passo, com a Sra. Graça a orientar e assistentes de IA em cada etapa.'"
                    />
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('register') }}"
                           class="inline-block px-8 py-3 rounded-full bg-white text-black font-semibold shadow hover:bg-gray-200 transition">
                            Criar conta
                        </a>
                        <a href="{{ route('login') }}"
                           class="inline-block px-8 py-3 rounded-full border border-white/30 text-white font-semibold hover:bg-white/10 transition">
                            Entrar
                        </a>
                    </div>
                </div>
                <div class="md:w-1/3 flex justify-center">
                    <img src="{{ asset(config('career_trail.mentor_avatar', 'img/graca-avatar.png')) }}"
                         alt="{{ config('career_trail.mentor_label', 'Sra. Graça') }}"
                         class="rounded-2xl shadow-xl w-full max-w-xs object-cover ring-4 ring-white/10" />
                </div>
            </div>
        </section>
    @endauth

    <div id="trilha-teaser" class="scroll-mt-24 py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900">Como é a trilha</h2>
                <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-600">
                    Um percurso sugerido — do currículo ao novo cargo — com objetivos claros em cada etapa e assistentes quando as desbloqueia.
                </p>
            </div>

            @if ($trailTeaserSteps->isEmpty())
                <p class="text-center text-gray-600">Em breve poderá ver aqui todas as etapas. Volte dentro de instantes.</p>
            @else
                <ol class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($trailTeaserSteps as $step)
                        <li class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-800">{{ $step->sort_order }}</span>
                            <h3 class="mt-3 text-lg font-semibold text-gray-900">{{ $step->title }}</h3>
                            @if ($step->short_description)
                                <p class="mt-2 text-sm text-gray-600">{{ Str::limit($step->short_description, 140) }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
                @guest
                    <div class="mt-12 text-center">
                        <p class="text-gray-700 mb-4">Para seguir a trilha completa com a Graça e os assistentes de IA, crie uma conta.</p>
                        <div class="flex justify-center gap-4 flex-wrap">
                            <a href="{{ route('register') }}" class="bg-black hover:bg-gray-800 text-white px-6 py-3 rounded-xl font-semibold transition">
                                Criar conta
                            </a>
                            <a href="{{ route('login') }}" class="border border-gray-800 text-gray-900 hover:bg-gray-100 px-6 py-3 rounded-xl font-semibold transition">
                                Já tenho conta
                            </a>
                        </div>
                    </div>
                @else
                    <div class="mt-12 text-center">
                        <a href="{{ route('career-trail.index') }}"
                           class="inline-flex items-center justify-center px-8 py-3 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">
                            Ver mapa da trilha
                        </a>
                    </div>
                @endguest
            @endif
        </div>
    </div>

    @if ($testimonials->count())
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-extrabold text-gray-900">O que dizem os usuários</h2>
                    <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-500">
                        Histórias reais de quem já usa o GratoAI na carreira.
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    @foreach ($testimonials as $testimonial)
                        <div class="bg-gray-50 rounded-lg p-6 shadow-sm">
                            <div class="flex items-center mb-4">
                                @php
                                    $img = $testimonial->author_image
                                        ? asset($testimonial->author_image)
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($testimonial->author_name) . '&background=23272a&color=fff&size=128';
                                @endphp
                                <img class="h-12 w-12 rounded-full object-cover" src="{{ $img }}" alt="{{ $testimonial->author_name }}">
                                <div class="ml-4">
                                    <h4 class="text-lg font-medium text-gray-900">{{ $testimonial->author_name ?? 'Usuário GratoAI' }}</h4>
                                    @if ($testimonial->author_role)
                                        <p class="text-gray-600 text-sm">{{ $testimonial->author_role }}</p>
                                    @endif
                                </div>
                            </div>
                            <p class="text-gray-700">“{{ $testimonial->content }}”</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <footer class="bg-black text-gray-300 mt-12">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-2">
                        <img src="{{ asset(config('branding.logo_main')) }}" alt="{{ config('app.name', 'GratoAI') }}" class="h-10 w-auto mr-2 invert">
                        <span class="text-xl font-bold text-white">GratoAI</span>
                    </div>
                    <p class="text-gray-400 mt-2 text-sm">Trilha de carreira com IA e a orientação da Sra. Graça — em cada etapa, o passo certo.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Links</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ url('/#trilha-teaser') }}" class="hover:text-white">A trilha</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white">Cadastrar</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-white">Entrar</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Contato</h3>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li>
                            <span class="inline-block w-5"><i class="fas fa-envelope"></i></span>
                            <a href="mailto:contato@gratoai.com" class="hover:text-white">contato@gratoai.com</a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Siga-nos</h3>
                    <div class="flex space-x-4 mt-2">
                        <a href="#" class="hover:text-white text-2xl" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="hover:text-white text-2xl" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="hover:text-white text-2xl" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <div class="mt-10 border-t border-gray-800 pt-6 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} GratoAI. Todos os direitos reservados.</p>
                <div class="flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('termos-uso') }}" class="text-gray-400 hover:text-white">Termos de uso</a>
                    <a href="{{ route('privacidade') }}" class="text-gray-400 hover:text-white">Política de Privacidade</a>
                </div>
            </div>
        </div>
    </footer>
</x-app-layout>
