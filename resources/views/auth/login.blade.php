<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Senha" />

            <x-text-input id="password" class="mt-1 block w-full"
                type="password"
                name="password"
                required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4 block">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">Manter sessão neste dispositivo</span>
            </label>
        </div>

        <p class="mt-4 text-sm text-gray-600">
            Precisa confirmar o e-mail?
            <a class="font-medium text-indigo-600 hover:underline focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-md" href="{{ route('verification.code.form') }}">Inserir código</a>
        </p>

        <div class="mt-6 flex items-center justify-end">
            @if (Route::has('password.request'))
                <a class="text-sm text-gray-600 underline hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    Esqueceu a senha?
                </a>
            @endif

            <x-primary-button class="ms-3">
                Entrar
            </x-primary-button>
        </div>
    </form>

    <div class="mt-8 border-t border-gray-200 pt-6">
        <p class="text-xs text-gray-500 mb-3">Reenviar e-mail de confirmação (conta ainda por confirmar):</p>
        <form method="POST" action="{{ route('verification.resend.public') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
            @csrf
            <div class="grow">
                <x-input-label for="resend-email-login" value="E-mail" />
                <x-text-input id="resend-email-login" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            </div>
            <x-secondary-button type="submit" class="justify-center shrink-0">Reenviar</x-secondary-button>
        </form>
    </div>
</x-guest-layout>
