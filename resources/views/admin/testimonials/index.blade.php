<x-app-layout>
    <h1 class="text-2xl font-bold mb-4">Depoimentos dos Usuários</h1>
    <table class="min-w-full bg-white rounded text-left">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Cargo</th>
                <th>Depoimento</th>
                <th>Agente</th>
                <th>Status</th>
                <th>Destaque</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($testimonials as $t)
            <tr>
                <td>{{ $t->author_name }}</td>
                <td>{{ $t->author_role }}</td>
                <td>{{ Str::limit($t->content, 60) }}</td>
                <td>{{ $t->agent?->name ?? 'Geral' }}</td>
                <td>
                    @if($t->is_approved)
                        <span class="text-green-600">Aprovado</span>
                    @else
                        <span class="text-red-600">Pendente</span>
                    @endif
                </td>
                <td>
                    @if($t->is_featured)
                        <span class="text-blue-600 font-bold">Destaque</span>
                    @else
                        -
                    @endif
                </td>
                <td>
                    <form action="{{ route('admin.testimonials.approve', $t) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <button class="text-green-500" title="Aprovar">✔️</button>
                    </form>
                    <form action="{{ route('admin.testimonials.reject', $t) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <button class="text-red-500" title="Rejeitar">❌</button>
                    </form>
                    <form action="{{ route('admin.testimonials.feature', $t) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <button class="text-blue-500" title="Destaque na Home">
                            @if($t->is_featured) 🔵 @else ⚪ @endif
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</x-app-layout>
