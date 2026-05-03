<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mensagens da Graça (trilha)
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg border border-green-200">{{ session('success') }}</div>
            @endif

            @if (! empty($filterStepId))
                <div class="mb-4 rounded-lg border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-950">
                    A filtrar mensagens do passo #{{ (int) $filterStepId }}.
                    <a href="{{ route('admin.career-trail-graca-messages.index') }}" class="font-semibold text-indigo-700 hover:underline">Mostrar todas</a>
                </div>
            @endif

            <div class="mb-4 flex flex-wrap justify-between gap-3">
                <p class="text-sm text-gray-600 max-w-2xl">
                    Cada registo é um bloco de texto mostrado junto ao avatar da mentora. O mesmo <em>slot</em> pode repetir-se com <em>ordem</em> diferente para vários parágrafos.
                </p>
                <a href="{{ route('admin.career-trail-graca-messages.create') }}"
                   class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                    Nova mensagem
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Processo</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Passo</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Slot</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Ordem</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Activo</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Pré-visualização</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Acções</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($messages as $m)
                                <tr>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $m->process_key }}</td>
                                    <td class="px-3 py-2">
                                        @if ($m->step)
                                            <span class="font-mono text-xs">{{ $m->step->slug }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $m->slot }}</td>
                                    <td class="px-3 py-2">{{ $m->sort_order }}</td>
                                    <td class="px-3 py-2">{{ $m->is_active ? 'Sim' : 'Não' }}</td>
                                    <td class="px-3 py-2 max-w-xs truncate text-gray-600" title="{{ $m->body }}">{{ Str::limit(strip_tags($m->body ?? ''), 80) }}</td>
                                    <td class="px-3 py-2 text-right whitespace-nowrap">
                                        <a href="{{ route('admin.career-trail-graca-messages.edit', $m) }}" class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">Editar</a>
                                        <form action="{{ route('admin.career-trail-graca-messages.destroy', $m) }}" method="post" class="inline" onsubmit="return confirm('Remover esta mensagem?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-gray-500">Sem mensagens. Execute o seeder ou crie uma nova.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
