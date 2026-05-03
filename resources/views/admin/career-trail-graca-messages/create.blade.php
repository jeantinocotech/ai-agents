<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nova mensagem da Graça</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('admin.career-trail-graca-messages.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Voltar</a>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.career-trail-graca-messages.store') }}" class="space-y-5">
                    @csrf
                    @include('admin.career-trail-graca-messages._form', [
                        'gracaMessage' => null,
                        'steps' => $steps,
                        'slots' => $slots,
                    ])
                    <div class="flex gap-3">
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
