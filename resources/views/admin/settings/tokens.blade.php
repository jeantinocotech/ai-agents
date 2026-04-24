<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Parâmetros de tokens
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.settings.tokens.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tokens de boas-vindas (novo usuário)</label>
                        <input type="number" name="tokens_welcome_amount" value="{{ old('tokens_welcome_amount', $values['tokens_welcome_amount']) }}" min="0" required
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        @error('tokens_welcome_amount')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tokens por renovação gratuita</label>
                        <input type="number" name="tokens_renewal_amount" value="{{ old('tokens_renewal_amount', $values['tokens_renewal_amount']) }}" min="0" required
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        @error('tokens_renewal_amount')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Intervalo entre renovações (dias)</label>
                        <input type="number" name="tokens_renewal_interval_days" value="{{ old('tokens_renewal_interval_days', $values['tokens_renewal_interval_days']) }}" min="1" required
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        @error('tokens_renewal_interval_days')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Saldo mínimo para enviar mensagem ao agente</label>
                        <input type="number" name="tokens_minimum_per_request" value="{{ old('tokens_minimum_per_request', $values['tokens_minimum_per_request']) }}" min="1" required
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        @error('tokens_minimum_per_request')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">ChatKit — tokens por resposta (ATS e assistente de CV)</label>
                        <input type="number" name="chatkit_tokens_per_session" value="{{ old('chatkit_tokens_per_session', $values['chatkit_tokens_per_session'] ?? '50') }}" min="0" required
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <p class="text-xs text-gray-500 mt-1">
                            Mesmo valor para: (1) débito após o assistente concluir a resposta à análise ATS (JD enviado) e (2) débito após <strong>cada resposta do assistente</strong> no chat simplificado da trilha (criar CV).
                            Abrir sessão ou renovar <code>client_secret</code> não debita. Use <strong>0</strong> para desativar estes débitos fixos.
                        </p>
                        @error('chatkit_tokens_per_session')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <hr class="my-6">

                    <h3 class="text-lg font-semibold text-gray-900">Cobrança vs custo OpenAI / Anthropic (USD)</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Atualize os preços por milhão (input/output) conforme a tabela do seu provedor.
                        O sistema estima o custo em USD da chamada e converte em tokens da plataforma usando <strong>tokens da plataforma por USD</strong>
                        (quanto maior, mais você cobra em relação ao custo estimado — margem de segurança).
                    </p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Modo de cobrança</label>
                        <select name="usage_billing_mode" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                            <option value="estimated_usd" @selected(old('usage_billing_mode', $values['usage_billing_mode'] ?? 'estimated_usd') === 'estimated_usd')>USD estimado → tokens da plataforma (recomendado)</option>
                            <option value="api_tokens" @selected(old('usage_billing_mode', $values['usage_billing_mode'] ?? '') === 'api_tokens')>1 token da API = 1 token da plataforma (legado)</option>
                        </select>
                        @error('usage_billing_mode')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tokens da plataforma por USD de custo estimado</label>
                        <input type="text" name="platform_tokens_per_usd" value="{{ old('platform_tokens_per_usd', $values['platform_tokens_per_usd'] ?? '2000') }}" required
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm" placeholder="2000">
                        <p class="text-xs text-gray-500 mt-1">Ex.: custo estimado US$ 0,01 → débito de ceil(0,01 × este valor) tokens.</p>
                        @error('platform_tokens_per_usd')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">OpenAI — input (US$ / 1M tokens)</label>
                            <input type="text" name="openai_input_price_per_million_usd" value="{{ old('openai_input_price_per_million_usd', $values['openai_input_price_per_million_usd'] ?? '5') }}" required
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                            @error('openai_input_price_per_million_usd')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">OpenAI — output (US$ / 1M tokens)</label>
                            <input type="text" name="openai_output_price_per_million_usd" value="{{ old('openai_output_price_per_million_usd', $values['openai_output_price_per_million_usd'] ?? '15') }}" required
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                            @error('openai_output_price_per_million_usd')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Anthropic — input (US$ / 1M tokens)</label>
                            <input type="text" name="anthropic_input_price_per_million_usd" value="{{ old('anthropic_input_price_per_million_usd', $values['anthropic_input_price_per_million_usd'] ?? '3') }}" required
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                            @error('anthropic_input_price_per_million_usd')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Anthropic — output (US$ / 1M tokens)</label>
                            <input type="text" name="anthropic_output_price_per_million_usd" value="{{ old('anthropic_output_price_per_million_usd', $values['anthropic_output_price_per_million_usd'] ?? '15') }}" required
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                            @error('anthropic_output_price_per_million_usd')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <hr class="my-6">

                    <h3 class="text-lg font-semibold text-gray-900">Pacote à venda (compra única)</h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Quantidade de tokens no pacote</label>
                        <input type="number" name="token_pack_amount" value="{{ old('token_pack_amount', $values['token_pack_amount']) }}" min="1" required
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        @error('token_pack_amount')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Preço do pacote (R$)</label>
                        <input type="text" name="token_pack_price" value="{{ old('token_pack_price', $values['token_pack_price']) }}" required
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm" placeholder="49.90">
                        @error('token_pack_price')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
