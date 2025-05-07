<x-app-layout>
<div class="p-4">
    <h2 class="text-xl font-bold mb-4">Passos do Agente: {{ $agent->name }}</h2>
    <a href="{{ route('admin.agents.steps.create', $agent) }}" class="bg-blue-500 text-white px-4 py-2 rounded mb-4 inline-block">Novo Passo</a>

    <table class="table-auto w-full">
        <thead>
            <tr>
                <th>Ordem</th>
                <th>Nome</th>
                <th>Input Esperado</th>
                <th>Palavras-chave</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($steps as $step)
                <tr>
                    <td class="border px-4 py-2">{{ $step->step_order }}</td>
                    <td class="border px-4 py-2">{{ $step->name }}</td>
                    <td class="border px-4 py-2">{{ $step->required_input }}</td>
                    <td class="border px-4 py-2">{{ implode(', ', $step->expected_keywords ?? []) }}</td>
                    <td class="border px-4 py-2">
                        <a href="{{ route('admin.agents.steps.edit', [$agent, $step]) }}" class="text-blue-600">Editar</a> |
                        <form action="{{ route('admin.agents.steps.destroy', [$agent, $step]) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600" onclick="return confirm('Tem certeza?')">Excluir</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
</x-app-layout>
