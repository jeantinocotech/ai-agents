<section class="rounded-lg border border-gray-100 p-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">Autenticação em dois passos (TOTP)</h2>
        <p class="mt-1 text-sm text-gray-600">
            Recomendado para maior segurança da conta após cadastrar método de pagamento.
        </p>
    </header>

    @if ($user->two_factor_confirmed_at)
        <p class="mt-4 rounded-md bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-950">
            Dois passos está <strong>ativo</strong>.
        </p>
        <form method="POST" action="{{ route('profile.two-factor.destroy') }}" class="mt-6 space-y-4">
            @csrf
            @method('DELETE')
            <div class="grid gap-4 sm:max-w-sm">
                <div>
                    <x-input-label for="disable_twof_password" value="{{ __('Password') }}" />
                    <x-text-input id="disable_twof_password" name="password" type="password" class="mt-1 block w-full"
                                  autocomplete="current-password" />
                    <x-input-error class="mt-2" :messages="$errors->disableTwoFactor->get('password')" />
                </div>
                <div>
                    <x-danger-button>Desativar dois passos</x-danger-button>
                </div>
            </div>
        </form>
        @if (session('status') === 'two-factor-disabled')
            <p class="mt-3 text-sm text-gray-700">Dois passos foi desactivado.</p>
        @endif
    @else
        <p class="mt-4 text-sm text-gray-700">Ainda não ativou o segundo fator.</p>
        <div class="mt-4">
            <a href="{{ route('profile.two-factor.start') }}"
               class="inline-flex rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                Configurar (QR + aplicativo autenticador)
            </a>
        </div>
    @endif
</section>
