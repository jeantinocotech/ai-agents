<x-app-layout>
    <div class="max-w-6xl mx-auto py-12 px-6">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Comprar tokens</h1>
            <p class="text-gray-600">
                Use tokens para conversar com os agentes. Após o pagamento confirmado, o saldo é creditado automaticamente.
            </p>
        </div>

        @if (session('info'))
            <div class="mb-4 p-4 bg-blue-50 text-blue-800 rounded">{{ session('info') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Seus dados</h2>

                <form id="token-checkout-form" class="space-y-4" data-url="{{ route('tokens.purchase.process') }}">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <input type="tel" name="phone" value="{{ old('phone', $user->phone ?? '') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                            <input type="text" name="document" value="{{ old('document', $user->cpf ?? '') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                            <input type="text" name="cep" id="cep" value="{{ old('cep', $user->cep ?? '') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                            <input type="text" name="address" id="address" value="{{ old('address', $user->address ?? '') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                            <input type="text" name="number" value="{{ old('number', $user->number ?? '') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                            <input type="text" name="city" id="city" value="{{ old('city', $user->city ?? '') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <input type="text" name="state" id="state" value="{{ old('state', $user->state ?? '') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div class="flex items-center mt-4">
                        <input type="checkbox" name="save_profile_data" value="1" class="mr-2" checked id="save_profile_data">
                        <label for="save_profile_data" class="text-sm text-gray-700">Salvar dados no perfil</label>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Pagamento</label>
                        <div class="space-y-2">
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="credit_card" class="mr-3" checked>
                                <span class="font-medium">Cartão de crédito</span>
                            </label>
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="pix" class="mr-3">
                                <span class="font-medium">PIX</span>
                            </label>
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="boleto" class="mr-3">
                                <span class="font-medium">Boleto</span>
                            </label>
                        </div>
                    </div>

                    <div id="card-details" class="mt-6 space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Cartão</h3>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Número</label>
                            <input type="text" name="card_number" class="w-full px-3 py-2 border rounded-md">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Validade MM/AA</label>
                                <input type="text" name="card_expiry" class="w-full px-3 py-2 border rounded-md" placeholder="MM/AA">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">CVV</label>
                                <input type="text" name="card_cvv" class="w-full px-3 py-2 border rounded-md">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Nome no cartão</label>
                            <input type="text" name="card_holder_name" class="w-full px-3 py-2 border rounded-md">
                        </div>
                    </div>

                    <div class="mt-8">
                        <button type="submit" id="checkout-btn"
                                class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-green-700 disabled:opacity-50">
                            <span id="button-text">Pagar</span>
                            <span id="spinner" class="hidden">Processando...</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Resumo</h2>
                <p class="text-gray-700 mb-2">Pacote atual (definido pelo administrador)</p>
                <p class="text-2xl font-bold text-green-600 mb-2">{{ number_format($tokensAmount, 0, ',', '.') }} tokens</p>
                <p class="text-lg text-gray-800">R$ {{ number_format($price, 2, ',', '.') }}</p>
                <p class="text-sm text-gray-500 mt-4">Saldo atual: <strong>{{ number_format(auth()->user()->token_balance, 0, ',', '.') }}</strong> tokens</p>
            </div>
        </div>
    </div>

    <div id="pix-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">PIX</h3>
                <button type="button" id="close-pix-modal" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <div class="text-center">
                <div class="flex justify-center mb-4">
                    <img id="pix-qrcode" src="" alt="QR Code" class="w-48 h-48 border p-2 hidden">
                </div>
                <p class="text-sm text-gray-600 mb-2">Copia e cola</p>
                <div class="flex">
                    <input id="pix-code" type="text" readonly class="flex-1 px-3 py-2 border rounded-l-md text-sm bg-gray-50">
                    <button type="button" id="copy-pix-code" class="bg-blue-600 text-white px-3 py-2 rounded-r-md">Copiar</button>
                </div>
                <p id="payment-status" class="mt-4 text-sm text-yellow-800 bg-yellow-50 p-3 rounded">Aguardando confirmação...</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('token-checkout-form');
            const submitButton = document.getElementById('checkout-btn');
            const buttonText = document.getElementById('button-text');
            const spinner = document.getElementById('spinner');
            const cardDetails = document.getElementById('card-details');
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const pixModal = document.getElementById('pix-modal');
            const pixQrcode = document.getElementById('pix-qrcode');
            const pixCode = document.getElementById('pix-code');
            let paymentId = null;
            let pollingInterval = null;

            function toggleCard() {
                const m = document.querySelector('input[name="payment_method"]:checked');
                if (m && m.value === 'credit_card') {
                    cardDetails.classList.remove('hidden');
                } else {
                    cardDetails.classList.add('hidden');
                }
            }
            paymentMethods.forEach(el => el.addEventListener('change', toggleCard));
            toggleCard();

            document.getElementById('close-pix-modal').addEventListener('click', () => pixModal.classList.add('hidden'));
            document.getElementById('copy-pix-code').addEventListener('click', function () {
                pixCode.select();
                document.execCommand('copy');
            });

            function checkPaymentStatus() {
                if (!paymentId) return;
                const fd = new FormData();
                fd.append('payment_id', paymentId);
                fetch('{{ route('tokens.payment.status') }}', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value }
                }).then(r => r.json()).then(data => {
                    if (data.success && data.is_paid) {
                        clearInterval(pollingInterval);
                        document.getElementById('payment-status').textContent = 'Pagamento confirmado! Redirecionando...';
                        setTimeout(() => { window.location.href = '{{ route('career-trail.index') }}'; }, 2000);
                    }
                }).catch(() => {});
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submitButton.disabled = true;
                buttonText.classList.add('hidden');
                spinner.classList.remove('hidden');

                const formData = new FormData(form);
                if (document.getElementById('save_profile_data').checked) {
                    formData.append('save_profile_data', '1');
                }

                fetch(form.dataset.url, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value }
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        if (data.is_pix && data.pix_info) {
                            paymentId = data.payment_id || data.asaas_subscription_id;
                            if (data.pix_info.encodedImage) {
                                pixQrcode.src = 'data:image/png;base64,' + data.pix_info.encodedImage;
                                pixQrcode.classList.remove('hidden');
                            }
                            pixCode.value = data.pix_info.payload || data.pix_info.copyPaste || '';
                            pixModal.classList.remove('hidden');
                            pollingInterval = setInterval(checkPaymentStatus, 5000);
                        } else if (data.invoice_url) {
                            window.location.href = data.invoice_url;
                        } else {
                            window.location.href = '{{ route('career-trail.index') }}';
                        }
                    } else {
                        alert(data.error || 'Erro ao processar.');
                    }
                }).catch(() => alert('Erro de rede.'))
                  .finally(() => {
                    submitButton.disabled = false;
                    buttonText.classList.remove('hidden');
                    spinner.classList.add('hidden');
                  });
            });

            const cepInput = document.getElementById('cep');
            if (cepInput) {
                cepInput.addEventListener('blur', function () {
                    const cep = this.value.replace(/\D/g, '');
                    if (cep.length === 8) {
                        fetch('https://viacep.com.br/ws/' + cep + '/json/').then(r => r.json()).then(data => {
                            if (!data.erro) {
                                document.getElementById('address').value = data.logradouro || '';
                                document.getElementById('city').value = data.localidade || '';
                                document.getElementById('state').value = data.uf || '';
                            }
                        });
                    }
                });
            }
        });
    </script>
</x-app-layout>
