<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Confirme o e-mail através do link ou do código de 6 dígitos na mensagem enviada.
        Também pode <a href="{{ route('verification.code.form') }}" class="font-medium text-indigo-600 hover:underline">introduzir o código aqui</a>.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Foi enviado um novo link de confirmação para o seu e-mail.
        </div>
    @endif

    <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button>
                Reenviar e-mail de confirmação
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Terminar sessão
            </button>
        </form>
    </div>
    <script>
        try {
            sessionStorage.removeItem('register_form_draft_v2');
            sessionStorage.removeItem('register_privacy_viewed_v2');
            sessionStorage.removeItem('register_terms_viewed_v2');
        } catch (e) {}
    </script>
</x-guest-layout>
