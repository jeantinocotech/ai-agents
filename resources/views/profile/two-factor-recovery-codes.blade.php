<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Chaves de recuperação (guarde já)
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-xl px-6">
            <div class="rounded-lg bg-amber-50 p-8 text-gray-900 ring-2 ring-amber-200 shadow">
                <p class="text-sm leading-relaxed text-amber-950">
                    Copie estas chaves para um lugar seguro. Serão necessárias se perder o telefone. Cada chave só pode usar <strong>uma vez</strong>.
                </p>
                <ul class="mt-4 space-y-2 font-mono text-sm">
                    @foreach ($recoveryCodes as $code)
                        <li class="rounded border border-amber-200 bg-white px-3 py-1">{{ $code }}</li>
                    @endforeach
                </ul>
                <div class="mt-8">
                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                        Concluir
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
