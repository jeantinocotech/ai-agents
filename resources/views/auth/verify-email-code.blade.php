<x-guest-layout>
    <div class="mb-4">
        <h2 class="text-lg font-medium text-gray-900">Confirmar e-mail</h2>
        <p class="mt-1 text-sm text-gray-600">
            Introduza o endereço e o código de 6 dígitos que enviámos (verifique também o spam).
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('verification.code.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="code" value="Código de 6 dígitos" />
            <x-text-input id="code" class="mt-1 block w-full tracking-widest font-mono" type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" :value="old('code')" required autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <x-primary-button>Confirmar</x-primary-button>
            <a class="text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-md" href="{{ route('login') }}">Iniciar sessão</a>
        </div>
    </form>

    <div class="mt-8 border-t border-gray-200 pt-6">
        <p class="text-xs text-gray-500 mb-3">Sem mensagem nova? Pedir novo e-mail de confirmação:</p>
        <form method="POST" action="{{ route('verification.resend.public') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
            @csrf
            <div class="grow">
                <x-input-label for="resend-email" value="E-mail" />
                <x-text-input id="resend-email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            </div>
            <x-secondary-button type="submit" class="justify-center shrink-0">Reenviar</x-secondary-button>
        </form>
    </div>
</x-guest-layout>
