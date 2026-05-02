@php($fromRegister = request('from') === 'register')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight w-full sm:w-auto sm:mr-auto">
                Política de Privacidade
            </h2>
            @if ($fromRegister)
                <a href="{{ route('register') }}" class="text-sm font-semibold text-green-700 hover:text-green-900 hover:underline">
                    Continuar o registo
                </a>
                <span class="hidden sm:inline text-gray-300 select-none">·</span>
                <a href="{{ route('home') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                    Ir ao início
                </a>
            @else
                <a href="{{ route('home') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                    Voltar ao início
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <article class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                @if ($fromRegister)
                    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-950">
                        Está a registar-se neste mesmo separador — use <a href="{{ route('register') }}" class="font-semibold text-green-900 underline underline-offset-2 hover:text-green-950">voltar ao registo</a> para continuar sem perder dados (nome e e-mail ficam recuperáveis neste equipamento). O botão voltar do navegador pode devolvê-lo à página inicial.
                    </div>
                @endif

                @include('legal.partials.privacy-body')

                <div class="mt-10 flex flex-wrap gap-4 border-t border-gray-200 pt-6 text-sm">
                    <a href="{{ route('termos-uso').($fromRegister ? '?from=register' : '') }}" class="font-medium text-indigo-700 hover:underline">Termos de uso</a>
                    <span class="text-gray-400">·</span>
                    <a href="{{ route('home') }}" class="font-medium text-indigo-700 hover:underline">Início</a>
                </div>
            </article>
        </div>
    </div>
</x-app-layout>
