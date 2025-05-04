<x-app-layout>
    <div class="p-4">
        <h2 class="text-xl font-bold mb-4">Novo Passo para {{ $agent->name }}</h2>

        <form method="POST" action="{{ route('agents.steps.store', $agent) }}">
            @csrf

            <div class="mb-4">
                <label>Ordem</label>
                <input type="number" name="step_order" class="border p-2 w-full" required>
            </div>

            <div class="mb-4">
                <label>Nome</label>
                <input type="text" name="name" class="border p-2 w-full" required>
            </div>

            <div class="mb-4">
                <label>Input Esperado</label>
                <input type="text" name="required_input" class="border p-2 w-full">
            </div>

            <div class="mb-4">
                <label>Palavras-chave (separadas por vírgula)</label>
                <input type="text" name="expected_keywords" class="border p-2 w-full">
            </div>

            <div class="mb-4">
                <label>Mensagem de Sistema</label>
                <textarea name="system_message" class="border p-2 w-full"></textarea>
            </div>

            <div class="mb-4">
                <label><input type="checkbox" name="can_continue" value="1"> Pode continuar sem este passo?</label>
            </div>

            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">Salvar</button>
        </form>
    </div>
</x-app-layout>
