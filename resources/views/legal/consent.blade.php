<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Consentimento legal
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl px-6 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white p-8 shadow">
                @if (session('warning'))
                    <p class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-950">{{ session('warning') }}</p>
                @endif
                <h1 class="text-lg font-semibold text-gray-900">Aceite obrigatório</h1>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">
                    A versão vigente dos documentos abaixo mudou ou ainda não tinha registado o seu aceite.
                    São necessários para tratarmos os seus dados (LGPD) e para usar a plataforma.
                </p>
                <div class="mt-4 rounded-md bg-slate-50 p-4 text-xs text-slate-600">
                    Política (versão {{ $privacyVersion }}) e Termos (versão {{ $termsVersion }}).
                </div>
                <form method="POST" action="{{ route('legal.consent.store') }}" class="mt-8 space-y-6">
                    @csrf
                    <div class="flex items-start gap-3 rounded-md border border-gray-100 p-3">
                        <input id="accept_privacy_policy" name="accept_privacy_policy" type="checkbox" value="1"
                               class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                               required />
                        <label for="accept_privacy_policy" class="text-sm text-gray-700">
                            Li e aceito a
                            <a href="{{ $privacyUrl }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-indigo-700 underline hover:text-indigo-900">
                                política de privacidade</a>.
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('accept_privacy_policy')" class="block" />

                    <div class="flex items-start gap-3 rounded-md border border-gray-100 p-3">
                        <input id="accept_terms" name="accept_terms" type="checkbox" value="1"
                               class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                               required />
                        <label for="accept_terms" class="text-sm text-gray-700">
                            Li e aceito os
                            <a href="{{ $termsUrl }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-indigo-700 underline hover:text-indigo-900">
                                termos de uso</a>.
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('accept_terms')" class="block" />

                    <div>
                        <x-primary-button>Continuar</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
