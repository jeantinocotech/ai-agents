<!-- resources/views/admin/agents/edit.blade.php -->
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center mb-6">
                        <a href="{{ route('admin.agents.index') }}" class="text-blue-500 hover:underline mr-4">
                            &larr; Voltar para lista
                        </a>
                        <h2 class="text-2xl font-bold">Editar Agente: {{ $agent->name }}</h2>
                    </div>
                    
                    @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <strong class="font-bold">Atenção!</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    @php
                        $integrationValue = old('integration', $agent->integration ?? \App\Models\Agent::INTEGRATION_OPENAI);
                        $showChatkitFields = $integrationValue === \App\Models\Agent::INTEGRATION_CHATKIT_WORKFLOW;
                    @endphp

                    <form action="{{ route('admin.agents.update', $agent->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nome do Agente</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $agent->name) }}" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Descrição</label>
                            <textarea name="description" id="description" rows="4" 
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('description', $agent->description) }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label for="integration" class="block text-gray-700 text-sm font-bold mb-2">Integração do chat</label>
                            <select name="integration" id="integration"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="{{ \App\Models\Agent::INTEGRATION_OPENAI }}" {{ $integrationValue === \App\Models\Agent::INTEGRATION_OPENAI ? 'selected' : '' }}>
                                    OpenAI (chat / assistente — comportamento atual)
                                </option>
                                <option value="{{ \App\Models\Agent::INTEGRATION_CHATKIT_WORKFLOW }}" {{ $integrationValue === \App\Models\Agent::INTEGRATION_CHATKIT_WORKFLOW ? 'selected' : '' }}>
                                    ChatKit — workflow do Agent Builder (wf_…)
                                </option>
                            </select>
                            <p class="text-sm text-gray-500 mt-1">ChatKit embute o widget; configure Workflow ID e uma API key com acesso ao ChatKit.</p>
                            <p class="text-sm text-amber-800 mt-1">Com <strong>OpenAI</strong> selecionado, os campos de workflow ficam ocultos. Escolha <strong>ChatKit</strong> para editar o Workflow ID e a versão.</p>
                        </div>

                        <div class="mb-4">
                            <label for="career_trail_step_slug" class="block text-gray-700 text-sm font-bold mb-2">Passo da trilha (opcional)</label>
                            @php
                                $trailSelected = old('career_trail_step_slug', $boundTrailStepSlug ?? '');
                            @endphp
                            <select name="career_trail_step_slug" id="career_trail_step_slug"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">— nenhum —</option>
                                @foreach (($trailSteps ?? []) as $step)
                                    <option value="{{ $step->slug }}" @selected((string) $trailSelected === (string) $step->slug)>
                                        Passo {{ $step->sort_order }} — {{ $step->title }} ({{ $step->slug }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Ao associar, este agente passa a desbloquear/ser usado nessa etapa da trilha (ex.: ATS). O passo 1 (CV) é configurado separadamente.</p>
                        </div>

                        <div id="chatkit-fields" @class([
                            'mb-4 space-y-4 rounded border border-amber-200 bg-amber-50/50 p-4',
                            'hidden' => ! $showChatkitFields,
                        ])>
                            <div>
                                <label for="chatkit_workflow_id" class="block text-gray-700 text-sm font-bold mb-2">Workflow ID</label>
                                <input type="text" name="chatkit_workflow_id" id="chatkit_workflow_id" value="{{ old('chatkit_workflow_id', $agent->chatkit_workflow_id) }}"
                                       placeholder="wf_…"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline font-mono text-sm">
                            </div>
                            <div>
                                <label for="chatkit_workflow_version" class="block text-gray-700 text-sm font-bold mb-2">Versão do workflow</label>
                                <input type="text" name="chatkit_workflow_version" id="chatkit_workflow_version" value="{{ old('chatkit_workflow_version', $agent->chatkit_workflow_version ?? '1') }}"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline w-32">
                            </div>
                            <p class="text-xs text-gray-600 leading-relaxed border-t border-amber-200/80 pt-3 mt-2">
                                <strong>API de integração</strong> (<code class="text-xs bg-white px-1 rounded">/api/chatkit/profile-documents</code>):
                                envie <code class="text-xs bg-white px-1 rounded">agent_id</code> = <span class="font-mono">{{ $agent->id }}</span>
                                e <code class="text-xs bg-white px-1 rounded">user</code> no formato
                                <span class="font-mono">user_</span><em>id_do_utilizador</em><span class="font-mono">_agent_{{ $agent->id }}</span>
                                (o mesmo valor que a sessão ChatKit envia à OpenAI; exemplo para o utilizador 12:
                                <span class="font-mono">user_12_agent_{{ $agent->id }}</span>).
                                O fluxo do Builder pode montar <code class="text-xs">user</code> a partir do contexto da sessão em vez de pedir ao utilizador final.
                            </p>
                        </div>

                        <div class="mb-4">
                            <label for="price" class="block text-gray-700 text-sm font-bold mb-2">Preço</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 pointer-events-none">R$</span>
                                <input 
                                    type="text" 
                                    name="price" 
                                    id="price" 
                                    value="{{ old('price', number_format($agent->price, 2, ',', '.')) }}" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 pl-10 text-gray-700 text-left leading-tight focus:outline-none focus:shadow-outline"
                                    placeholder="0,00"
                                >
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Digite apenas números. Ex: 12345 vira R$ 123,45</p>
                            
                            <!-- Campo hidden para enviar o valor correto para o servidor -->
                            <input type="hidden" name="price_formatted" id="price_formatted" value="{{ $agent->price }}">
                        </div>

                        <div class="mb-4">
                            <label for="organization" class="block text-gray-700 text-sm font-bold mb-2">Organization</label>
                            <textarea name="organization" id="organization" rows="4" 
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('organization', $agent->organization) }}</textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="project_id" class="block text-gray-700 text-sm font-bold mb-2">Project Id</label>
                            <textarea name="project_id" id="project_id" rows="4" 
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('project_id', $agent->project_id) }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label for="assistant_id" class="block text-gray-700 text-sm font-bold mb-2">Assistant Id</label>
                            <textarea name="assistant_id" id="assistant_id" rows="4" 
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('assistant_id', $agent->assistant_id) }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label for="system_prompt" class="block text-gray-700 text-sm font-bold mb-2">System Prompt</label>
                            <textarea name="system_prompt" id="system_prompt" rows="4" 
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('system_prompt', $agent->system_prompt) }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Imagem</label>

                            <img id="agent-image-preview"
                                src="{{ asset('storage/' . $agent->image_path) }}"
                                alt="{{ $agent->name }}"
                                class="w-32 h-32 object-cover rounded mb-2">

                            <input type="file" name="image" id="image" accept="image/*" onchange="previewImage(event)"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <p class="text-sm text-gray-500 mt-1">Formatos aceitos: JPG, PNG, GIF (max 2MB)</p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Vídeo Atual do YouTube</label>
                            <div class="aspect-w-16 aspect-h-9 mb-2">
                                <iframe 
                                    src="https://www.youtube.com/embed/{{ $agent->youtube_video_id }}" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen
                                    class="w-full h-48"
                                ></iframe>
                            </div>
                            
                            <label for="youtube_video_id" class="block text-gray-700 text-sm font-bold mb-2">ID do Vídeo no YouTube</label>
                            <input type="text" name="youtube_video_id" id="youtube_video_id" 
                                   value="{{ old('youtube_video_id', $agent->youtube_video_id) }}" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <p class="text-sm text-gray-500 mt-1">Ex: "dQw4w9WgXcQ" da URL https://www.youtube.com/watch?v=dQw4w9WgXcQ</p>
                        </div>
                        
                        <div class="mb-4">
                            <label for="api_key" class="block text-gray-700 text-sm font-bold mb-2">Chave API (opcional)</label>
                            <input type="text" name="api_key" id="api_key" value="{{ old('api_key', $agent->api_key) }}" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        
                        <div class="mb-6">
                            <label for="model_type" class="block text-gray-700 text-sm font-bold mb-2">Tipo de Modelo</label>
                            <select name="model_type" id="model_type" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Selecione um modelo</option>
                                <option value="GPT-3.5" {{ old('model_type', $agent->model_type) == 'GPT-3.5' ? 'selected' : '' }}>GPT-3.5</option>
                                <option value="GPT-4" {{ old('model_type', $agent->model_type) == 'GPT-4' ? 'selected' : '' }}>GPT-4</option>
                                <option value="Claude" {{ old('model_type', $agent->model_type) == 'Claude' ? 'selected' : '' }}>Claude</option>
                                <option value="Custom" {{ old('model_type', $agent->model_type) == 'Custom' ? 'selected' : '' }}>Modelo Personalizado</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <x-input-label for="is_active" :value="__('Ativado')" />
                            <div class="flex items-center gap-4">
                                <label class="flex items-center">
                                    <input type="radio" name="is_active" value="1" {{ old('is_active', $agent->is_active ?? 1) == 1 ? 'checked' : '' }} />
                                    <span>{{ __('Ativo') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="is_active" value="0" {{ old('is_active', $agent->is_active ?? 1) == 0 ? 'checked' : '' }} />
                                    <span>{{ __('Inativo') }}</span>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-between mb-6">
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Atualizar Agente
                            </button>
                        </div>
                    </form>
                        
                    <!-- Passos do agente -->    
                    <div class="mb-6">
                        <label for="model_type" class="block text-gray-700 text-sm font-bold mb-2">Passos do Agente</label>
                        <table class="w-full table-auto mb-4 text-left">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2">#</th>
                                    <th class="px-4 py-2">Nome</th>
                                    <th class="px-4 py-2">Input Obrigatório</th>
                                    <th class="px-4 py-2">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($steps as $step)
                                <tr class="border-t">
                                    <td class="px-4 py-2">{{ $step->step_order }}</td>
                                    <td class="px-4 py-2">{{ $step->name }}</td>
                                    <td class="px-4 py-2">{{ $step->required_input }}</td>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('admin.agents.steps.edit', [$agent, $step]) }}" class="text-blue-600 hover:underline">Editar</a>
                                        <form action="{{ route('admin.agents.steps.destroy', [$agent, $step]) }}" method="POST" target="_self" class="inline ml-2">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-block text-red-600 hover:underline" onclick="return confirm('Tem certeza que deseja excluir este passo?')">
                                                Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <a href="{{ route('admin.agents.steps.create', $agent) }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            +
                        </a>
                    </div>

                   
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<style>
    /* Adicione este CSS no final do seu blade ou em um arquivo CSS separado */
    .price-input-container {
        position: relative;
    }
    
    .price-input-container input[type="text"] {
        text-align: right;
        padding-left: 2.5rem;
    }
    
    .currency-symbol {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        pointer-events: none;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const intSel = document.getElementById('integration');
        if (intSel) {
            function toggleChatKitFields() {
                const box = document.getElementById('chatkit-fields');
                if (!box) {
                    return;
                }
                box.classList.toggle('hidden', intSel.value !== '{{ \App\Models\Agent::INTEGRATION_CHATKIT_WORKFLOW }}');
            }
            intSel.addEventListener('change', toggleChatKitFields);
            toggleChatKitFields();
        }

        const priceInput = document.getElementById('price');
        const priceFormattedInput = document.getElementById('price_formatted');

        if (! priceInput || ! priceFormattedInput) {
            return;
        }

        function formatPrice(input) {
            let value = input.value.replace(/\D/g, '');

            if (value === '') {
                input.value = '';
                priceFormattedInput.value = '0';

                return;
            }

            if (value.length > 10) {
                value = value.slice(0, 10);
            }

            let numericValue = parseInt(value, 10);
            let formattedValue = (numericValue / 100).toFixed(2);

            let parts = formattedValue.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            input.value = parts.join(',');

            priceFormattedInput.value = formattedValue;
        }

        priceInput.addEventListener('input', function() {
            formatPrice(this);
        });

        if (priceInput.value) {
            let cleanValue = priceInput.value.replace(/\D/g, '');
            if (cleanValue) {
                priceInput.value = cleanValue;
                formatPrice(priceInput);
            }
        }

        priceInput.addEventListener('paste', function(e) {
            e.preventDefault();
            let paste = (e.clipboardData || window.clipboardData).getData('text');
            let numbers = paste.replace(/\D/g, '');
            if (numbers) {
                this.value = numbers;
                formatPrice(this);
            }
        });

        priceInput.addEventListener('keypress', function(e) {
            if (!/\d/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                if (!(e.key === 'a' && e.ctrlKey) && !(e.key === 'c' && e.ctrlKey) && !(e.key === 'v' && e.ctrlKey)) {
                    e.preventDefault();
                }
            }
        });

        const form = document.querySelector('form[action*="admin/agents"]') || document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function() {});
        }
    });
</script>

<script>
function previewImage(event) {
    const imagePreview = document.getElementById('agent-image-preview');
    const file = event.target.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}
</script>
