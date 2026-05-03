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
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            @auth
                @if (! empty($careerTrailContext))
                    <x-career-trail-banner :context="$careerTrailContext" />
                @endif
            @endauth

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

        @stack('scripts')

       <!-- Cookie Consent Banner -->
        <div id="cookie-banner" class="fixed bottom-0 left-0 w-full z-50 flex items-center justify-between bg-[#181D20] text-white p-4 shadow-lg" style="display:none;">
            <div class="flex-1 text-sm md:text-base">
                Este site pode guardar cookies no seu dispositivo. O tratamento dos seus dados como utilizador registado está descrito na
                <a href="{{ route('privacidade') }}" class="underline text-blue-300 hover:text-blue-200" target="_blank" rel="noopener noreferrer">Política de Privacidade</a>.
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
            /** Preferências de cookies (navegador). O tratamento LGPD dos dados da conta faz-se em registo e em /conta/consentimento. */
            document.addEventListener('DOMContentLoaded', function () {
                const consent = localStorage.getItem('cookie_consent');
                const banner = document.getElementById('cookie-banner');
                const acceptBtn = document.getElementById('accept-cookies');
                const rejectBtn = document.getElementById('reject-cookies');

                if (!banner || !acceptBtn || !rejectBtn) {
                    return;
                }

                banner.style.display = consent ? 'none' : 'flex';

                acceptBtn.onclick = function () {
                    localStorage.setItem('cookie_consent', 'accepted');
                    banner.style.display = 'none';
                };

                rejectBtn.onclick = function () {
                    localStorage.setItem('cookie_consent', 'rejected');
                    banner.style.display = 'none';
                };
            });

            function resetCookieConsent() {
                localStorage.removeItem('cookie_consent');
                location.reload();
            }
        </script>
    </body>
</html>



