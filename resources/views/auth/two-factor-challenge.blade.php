<x-guest-layout>
    <div class="mb-6 text-center text-gray-900">
        <h1 class="text-lg font-semibold">Autenticação em dois passos</h1>
        <p class="mt-2 text-sm text-gray-600">Digite o código do aplicativo ou uma chave de recuperação.</p>
    </div>

    <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="space-y-6">
        @csrf
        <div>
            <x-input-label for="code" value="Código" />
            <x-text-input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9A-Za-z-]*"
                          class="mt-1 block w-full tracking-wider" autofocus autocomplete="one-time-code" required />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>
        <div class="flex justify-end gap-4">
            <a href="{{ route('login') }}" class="inline-flex rounded-md px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900"> Voltar ao login </a>
            <x-primary-button>{{ __('Continue') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
