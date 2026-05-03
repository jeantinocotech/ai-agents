<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Trilha — passos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg border border-green-200">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <p class="text-sm text-gray-600">
                        Edite o <strong>título</strong> e a <strong>descrição curta</strong> de cada etapa. Os textos <strong>ao lado do avatar da Graça</strong> estão em
                        <a href="{{ route('admin.career-trail-graca-messages.index') }}" class="font-semibold text-indigo-700 hover:underline">Mensagens da Graça (trilha)</a>.
                        O nome e o avatar da mentora na navegação vêm de <code class="text-xs bg-gray-100 px-1 rounded">config/career_trail.php</code> / variáveis de ambiente.
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordem</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activo</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acções</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Graça</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($steps as $s)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $s->sort_order }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-700">{{ $s->slug }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $s->title }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        @if ($s->is_active)
                                            <span class="text-green-700 font-medium">Sim</span>
                                        @else
                                            <span class="text-gray-500">Não</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                        <a href="{{ route('admin.career-trail-steps.edit', $s) }}"
                                           class="text-indigo-600 hover:text-indigo-900 font-medium">Editar</a>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                        <a href="{{ route('admin.career-trail-graca-messages.index', ['career_trail_step_id' => $s->id]) }}"
                                           class="text-violet-700 hover:text-violet-900 font-medium">Mensagens</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
