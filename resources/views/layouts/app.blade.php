<!DOCTYPE html>

    @auth
    <script>
        window.Laravel = { userId: {{ Auth::id() }} };
    </script>
    @endauth

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;700;800&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/minimalist.css') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

       <!-- Cookie Consent Banner -->
        <div id="cookie-banner" class="fixed bottom-0 left-0 w-full z-50 flex items-center justify-between bg-[#181D20] text-white p-4 shadow-lg" style="display:none;">
            <div class="flex-1 text-sm md:text-base">
                Este site utiliza cookies para melhorar sua experiência. Veja nossa
                <a href="{{ route('privacidade') }}" class="underline text-blue-300 hover:text-blue-200" target="_blank">Política de Privacidade</a>.
            </div>
            <div class="flex gap-2 ml-4">
                <button id="accept-cookies" class="px-4 py-2 bg-green-600 rounded text-white font-semibold hover:bg-green-700 transition">
                    Aceitar
                </button>
                <button id="reject-cookies" class="px-4 py-2 bg-gray-700 rounded text-gray-300 hover:bg-gray-800 transition">
                    Recusar
                </button>
            </div>
        </div>

        <script>
            function checkCookieConsent() {
                console.log('Verificando consentimento de cookies...');
                
                const consent = localStorage.getItem('cookie_consent');
                console.log('Status atual do consentimento:', consent);
                
                const banner = document.getElementById('cookie-banner');
                
                if (!consent) {
                    console.log('Nenhum consentimento encontrado, mostrando banner');
                    if (banner) {
                        banner.style.display = 'flex';
                    } else {
                        console.error('Banner de cookies não encontrado no DOM');
                    }
                } else {
                    console.log('Consentimento já registrado:', consent);
                    if (banner) {
                        banner.style.display = 'none';
                    }
                    
                    // Se usuário está logado e já aceitou no localStorage, sincroniza com o banco
                    syncConsentWithBackend(consent);
                }
            }

            function syncConsentWithBackend(consent) {
                // Só sincroniza se estiver logado e tiver aceitado
                if (window.Laravel && Laravel.userId && consent === 'accepted') {
                    console.log('Sincronizando consentimento com o backend...');
                    
                    fetch('{{ route("privacy.sync") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            localStorage_consent: consent
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Sincronização concluída:', data);
                    })
                    .catch(error => {
                        console.error('Erro na sincronização:', error);
                    });
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                console.log('DOM carregado, inicializando cookie consent...');
                
                // Aguarda um pouco para garantir que o DOM está completamente renderizado
                setTimeout(function() {
                    checkCookieConsent();
                    
                    // Verifica se há flag de sessão para sincronizar
                    checkSessionFlag();
                }, 100);

                const acceptBtn = document.getElementById('accept-cookies');
                const rejectBtn = document.getElementById('reject-cookies');
                const banner = document.getElementById('cookie-banner');

                if (!acceptBtn) {
                    console.error('Botão de aceitar não encontrado');
                    return;
                }

                if (!rejectBtn) {
                    console.error('Botão de rejeitar não encontrado');
                    return;
                }

                acceptBtn.onclick = function () {
                    console.log('Cookie aceito pelo usuário');
                    localStorage.setItem('cookie_consent', 'accepted');
                    
                    if (banner) {
                        banner.style.display = 'none';
                    }

                    // Envia para o backend se estiver logado
                    if (window.Laravel && Laravel.userId) {
                        console.log('Enviando consentimento para o backend...');
                        fetch('{{ route("privacy.accept") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({})
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Consentimento salvo no backend:', data);
                        })
                        .catch(error => {
                            console.error('Erro ao salvar consentimento:', error);
                        });
                    }
                };

                rejectBtn.onclick = function () {
                    console.log('Cookie rejeitado pelo usuário');
                    localStorage.setItem('cookie_consent', 'rejected');
                    
                    if (banner) {
                        banner.style.display = 'none';
                    }
                };
            });

            function checkSessionFlag() {
                // Verifica se há um flag de sessão indicando que precisa sincronizar
                @if(session('check_privacy_consent'))
                    console.log('Flag de sessão detectado, verificando localStorage...');
                    const consent = localStorage.getItem('cookie_consent');
                    if (consent === 'accepted') {
                        console.log('Consentimento encontrado no localStorage, sincronizando...');
                        syncConsentWithBackend(consent);
                    }
                @endif
            }

            // Função para resetar o consentimento (útil para testes)
            function resetCookieConsent() {
                localStorage.removeItem('cookie_consent');
                location.reload();
            }

        </script>
    </body>
</html>



