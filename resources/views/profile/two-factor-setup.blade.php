<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ativar autenticação em dois passos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl px-6 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-8 shadow">
                <p class="text-sm text-gray-600">
                    1. Leia este código QR na aplicação (Google Authenticator, 1Password, etc.) com o nome <strong>{{ config('app.name') }}</strong>.
                </p>
                <div class="mt-4 flex justify-center overflow-x-auto rounded border border-gray-100 p-6">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ urlencode($qrUrl) }}" alt="QR código 2FA" width="240" height="240" />
                </div>
                <p class="mt-4 text-xs text-gray-500">Se não usar QR: chave secreta (base32)</p>
                <p class="mt-1 rounded bg-gray-900 px-3 py-2 font-mono text-sm text-green-400">{{ $secret }}</p>

                <form method="POST" action="{{ route('profile.two-factor.confirm') }}" class="mt-8 max-w-xs space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="code" value="Primeiro código (6 dígitos)" />
                        <x-text-input id="code" name="code" type="text" inputmode="numeric" class="mt-1 block w-full tracking-widest" required autocomplete="one-time-code" />
                        <x-input-error :messages="$errors->get('code')" />
                    </div>
                    <div class="flex gap-4">
                        <x-primary-button>Confirmar e ativar</x-primary-button>
                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 underline">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
