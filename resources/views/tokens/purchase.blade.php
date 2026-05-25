<x-app-layout>
    <div class="max-w-6xl mx-auto py-10 px-6">
        <x-graca-orientation-panel :page-key="\App\Support\GracaPanelPreferences::PAGE_TOKENS">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                <div class="shrink-0 rounded-2xl bg-white p-1 shadow ring-1 ring-violet-100">
                    <x-graca-avatar size="md" />
                </div>
                <div class="min-w-0 flex-1 text-sm leading-relaxed text-slate-700">
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Tokens</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Comprar tokens</h1>
                    <p class="mt-2">
                        Use tokens para conversar com os agentes. Após o pagamento confirmado, o saldo é creditado automaticamente.
                    </p>
                    <div class="mt-3">
                        <x-token-policy-note />
                    </div>
                </div>
            </div>
        </x-graca-orientation-panel>

        @if (session('info'))
            <div class="mb-4 p-4 bg-blue-50 text-blue-800 rounded">{{ session('info') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Seus dados</h2>

                <form id="token-checkout-form" class="space-y-4" data-url="{{ route('tokens.purchase.process') }}">
                    @csrf

                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-2.5">
                        <div class="grid grid-cols-3 gap-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600">Pacotes</p>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600">Tokens</p>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-600">Total</p>

                            <div>
                                <input id="token-pack-quantity" name="quantity"
                                       type="number" inputmode="numeric" min="1" max="100" step="1"
                                       value="{{ (int) old('quantity', 1) }}"
                                       class="w-20 bg-transparent p-0 text-sm font-semibold tabular-nums text-slate-900 focus:outline-none" />
                            </div>
                            <p class="self-center text-sm font-semibold tabular-nums text-slate-900">
                                <span id="token-pack-total-tokens-inline">{{ number_format($tokensAmount, 0, ',', '.') }}</span>
                            </p>
                            <p class="self-center text-sm font-semibold tabular-nums text-slate-900">
                                R$ <span id="token-pack-total-price-inline">{{ number_format($price, 2, ',', '.') }}</span>
                            </p>
                        </div>
                    </div>

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
                            <label class="block text-sm font-medium text-gray-700 mb-1">CPF ou CNPJ</label>
                            <input type="text" name="document" id="document-tax-id" autocomplete="off"
                                   inputmode="numeric" maxlength="18"
                                   value="{{ old('document', $user->cpf ?? '') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                   aria-describedby="document-tax-id-hint">
                            <p id="document-tax-id-hint" class="mt-1 text-sm text-red-600 hidden" role="status"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                            <input type="text" name="cep" id="cep" required inputmode="numeric" maxlength="9"
                                   autocomplete="postal-code" placeholder="00000-000"
                                   value="{{ old('cep', $user->cep ?? '') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                   aria-describedby="cep-feedback">
                            <p id="cep-feedback" class="mt-1 text-sm hidden" role="status"></p>
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
                <p class="text-2xl font-bold text-green-600 mb-2">
                    <span id="token-pack-total-amount">{{ number_format($tokensAmount, 0, ',', '.') }}</span> tokens
                </p>
                <p class="text-lg text-gray-800">
                    R$ <span id="token-pack-total-price">{{ number_format($price, 2, ',', '.') }}</span>
                </p>
                <p class="text-sm text-gray-500 mt-4">Saldo atual: <strong>{{ number_format(auth()->user()->token_balance, 0, ',', '.') }}</strong> tokens</p>
                <p class="text-sm mt-3">
                    <a href="{{ route('tokens.history') }}" class="text-green-700 font-medium hover:underline">Ver histórico de compras</a>
                </p>
            </div>
        </div>
    </div>

    <div id="pix-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-slate-900">Pagar com PIX</h3>
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

    <div id="boleto-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-slate-900">Boleto gerado</h3>
                <button type="button" id="close-boleto-modal" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <p id="boleto-message" class="text-sm text-slate-600 mb-4"></p>
            <p id="boleto-due-date" class="text-sm text-slate-700 mb-3 hidden"></p>
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Linha digitável</label>
            <div class="flex mb-4">
                <input id="boleto-barcode" type="text" readonly class="flex-1 px-3 py-2 border border-slate-200 rounded-l-lg text-sm bg-slate-50">
                <button type="button" id="copy-boleto-barcode" class="bg-violet-600 text-white px-3 py-2 rounded-r-lg text-sm hover:bg-violet-700">Copiar</button>
            </div>
            <a id="boleto-pdf-link" href="#" target="_blank" rel="noopener noreferrer"
               class="mb-4 inline-block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-center text-sm font-medium text-slate-800 hover:bg-slate-100 hidden">
                Baixar PDF do boleto
            </a>
            <p id="boleto-status" class="text-sm text-amber-900 bg-amber-50 p-3 rounded-lg">Aguardando confirmação do pagamento…</p>
            <p class="mt-3 text-xs text-slate-500">Os tokens serão creditados após a compensação. Pode fechar este modal — a verificação continua em segundo plano.</p>
        </div>
    </div>

    <div id="card-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 ring-1 ring-slate-200">
            <div class="flex justify-between items-center mb-4">
                <h3 id="card-modal-title" class="text-xl font-bold text-slate-900">Pagamento com cartão</h3>
                <button type="button" id="close-card-modal" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <p id="card-message" class="text-sm text-slate-600 mb-4"></p>
            <a id="card-secondary-link" href="#" target="_blank" rel="noopener noreferrer"
               class="mb-4 hidden w-full rounded-lg border border-violet-200 bg-violet-50 px-4 py-2.5 text-center text-sm font-medium text-violet-900 hover:bg-violet-100">
                Concluir verificação
            </a>
            <p id="card-status" class="text-sm text-amber-900 bg-amber-50 p-3 rounded-lg">Aguardando confirmação…</p>
            <p class="mt-3 text-xs text-slate-500">Não é necessário sair do site. Esta página atualiza automaticamente.</p>
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
            const boletoModal = document.getElementById('boleto-modal');
            const boletoMessage = document.getElementById('boleto-message');
            const boletoDueDate = document.getElementById('boleto-due-date');
            const boletoBarcode = document.getElementById('boleto-barcode');
            const boletoPdfLink = document.getElementById('boleto-pdf-link');
            const boletoStatus = document.getElementById('boleto-status');
            const cardModal = document.getElementById('card-modal');
            const cardModalTitle = document.getElementById('card-modal-title');
            const cardMessage = document.getElementById('card-message');
            const cardSecondaryLink = document.getElementById('card-secondary-link');
            const cardStatus = document.getElementById('card-status');

            function resetCheckoutButtonState() {
                submitButton.disabled = false;
                buttonText.classList.remove('hidden');
                spinner.classList.add('hidden');
            }
            const docInput = document.getElementById('document-tax-id');
            const docHint = document.getElementById('document-tax-id-hint');

            function setDocTaxHint(text) {
                if (!docHint) return;
                if (text) {
                    docHint.textContent = text;
                    docHint.classList.remove('hidden');
                } else {
                    docHint.textContent = '';
                    docHint.classList.add('hidden');
                }
            }

            function validateDocumentTaxId(opts) {
                const show = opts && opts.showHint;
                if (!docInput || typeof window.BrazilTaxId === 'undefined') {
                    return true;
                }
                const result = window.BrazilTaxId.validateBrazilTaxIdField(docInput.value);
                if (result.ok) {
                    docInput.classList.remove('border-red-500');
                    setDocTaxHint('');
                    return true;
                }
                docInput.classList.add('border-red-500');
                if (show) {
                    setDocTaxHint(result.message);
                }
                return false;
            }

            if (docInput) {
                docInput.addEventListener('blur', function () {
                    if (!docInput.value.trim()) {
                        setDocTaxHint('');
                        docInput.classList.remove('border-red-500');
                        return;
                    }
                    validateDocumentTaxId({ showHint: true });
                });
                docInput.addEventListener('input', function () {
                    if (docHint && !docHint.classList.contains('hidden')) {
                        validateDocumentTaxId({ showHint: true });
                    }
                });
            }

            let paymentId = null;
            let orderId = null;
            let pollingInterval = null;
            const thankYouUrlFor = (id) => @json(route('tokens.thank-you', ['order' => 0])).replace(/\/0$/, '/' + id);

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

            const qtySel = document.getElementById('token-pack-quantity');
            const totalAmountEl = document.getElementById('token-pack-total-amount');
            const totalPriceEl = document.getElementById('token-pack-total-price');
            const totalTokensInlineEl = document.getElementById('token-pack-total-tokens-inline');
            const totalPriceInlineEl = document.getElementById('token-pack-total-price-inline');
            const baseTokens = {{ (int) $tokensAmount }};
            const basePrice = {{ json_encode((float) $price) }};
            const fmtTokens = new Intl.NumberFormat('pt-BR');
            const fmtMoney = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            function clampQty(raw) {
                const v = parseInt(String(raw || '1'), 10);
                if (Number.isNaN(v)) return 1;
                const c = Math.max(1, Math.min(100, v));
                if (c <= 10) {
                    return c;
                }
                // Para exibição/calculo: 11–14 conta como 15 (primeiro valor válido após 10).
                if (c < 15) {
                    return 15;
                }
                const snapped = Math.round(c / 5) * 5;
                return Math.max(15, Math.min(100, snapped));
            }

            function stepUpQty(current) {
                const c = clampQty(current);
                if (c < 10) return c + 1;
                if (c === 10) return 15;
                return Math.min(100, c + 5);
            }

            function stepDownQty(current) {
                const c = clampQty(current);
                if (c > 15) return c - 5;
                if (c === 15) return 10;
                return Math.max(1, c - 1);
            }

            function syncPackTotals() {
                if (!qtySel) return;
                const q = clampQty(qtySel.value);
                if (totalAmountEl) totalAmountEl.textContent = fmtTokens.format(baseTokens * q);
                if (totalPriceEl) totalPriceEl.textContent = fmtMoney.format(basePrice * q);
                if (totalTokensInlineEl) totalTokensInlineEl.textContent = fmtTokens.format(baseTokens * q);
                if (totalPriceInlineEl) totalPriceInlineEl.textContent = fmtMoney.format(basePrice * q);
            }
            if (qtySel) {
                qtySel.addEventListener('change', syncPackTotals);
                qtySel.addEventListener('input', syncPackTotals);
                syncPackTotals();
            }

            function showModal(el) {
                if (!el) return;
                el.classList.remove('hidden');
                el.classList.add('flex');
            }

            function hideModal(el) {
                if (!el) return;
                el.classList.add('hidden');
                el.classList.remove('flex');
            }

            function markStatusPaid(el) {
                if (!el) return;
                el.textContent = 'Pagamento confirmado. A redirecionar…';
                el.classList.remove('text-amber-900', 'bg-amber-50', 'text-yellow-800', 'bg-yellow-50');
                el.classList.add('text-emerald-900', 'bg-emerald-50');
            }

            document.getElementById('close-pix-modal').addEventListener('click', () => hideModal(pixModal));
            document.getElementById('copy-pix-code').addEventListener('click', function () {
                pixCode.select();
                document.execCommand('copy');
            });
            document.getElementById('close-boleto-modal').addEventListener('click', () => hideModal(boletoModal));
            document.getElementById('copy-boleto-barcode').addEventListener('click', function () {
                if (!boletoBarcode || !boletoBarcode.value) return;
                boletoBarcode.select();
                document.execCommand('copy');
            });
            document.getElementById('close-card-modal').addEventListener('click', () => hideModal(cardModal));

            function redirectToThankYou() {
                if (orderId) {
                    window.location.href = thankYouUrlFor(orderId);
                    return;
                }
                window.location.href = '{{ route('career-trail.index') }}';
            }

            function setPaymentConfirmedUi() {
                clearInterval(pollingInterval);
                const msg = 'Pagamento confirmado. A redirecionar…';
                const ps = document.getElementById('payment-status');
                if (ps) markStatusPaid(ps);
                markStatusPaid(boletoStatus);
                markStatusPaid(cardStatus);
                setTimeout(redirectToThankYou, 2000);
            }

            function checkPaymentStatus() {
                if (!paymentId) return;
                const fd = new FormData();
                fd.append('payment_id', paymentId);
                fetch('{{ route('tokens.payment.status') }}', {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                }).then(r => r.json()).then(data => {
                    if (data.order_id) {
                        orderId = data.order_id;
                    }
                    if (data.success && data.is_paid) {
                        setPaymentConfirmedUi();
                    }
                }).catch(() => {});
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submitButton.disabled = true;
                buttonText.classList.add('hidden');
                spinner.classList.remove('hidden');

                if (!validateDocumentTaxId({ showHint: true })) {
                    alert((docHint && docHint.textContent) ? docHint.textContent : 'CPF ou CNPJ inválido.');
                    docInput && docInput.focus();
                    resetCheckoutButtonState();
                    return;
                }

                if (!validateCepForCheckout()) {
                    resetCheckoutButtonState();
                    return;
                }

                const formData = new FormData(form);
                if (document.getElementById('save_profile_data').checked) {
                    formData.append('save_profile_data', '1');
                }

                fetch(form.dataset.url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                }).then(async function (r) {
                    let data = {};
                    try {
                        data = await r.json();
                    } catch (e) {
                        console.error('[tokens/checkout] Resposta não é JSON', r.status);
                        alert('Erro de rede ou resposta inválida (' + r.status + '). Veja DevTools → Rede.');
                        return;
                    }
                    if (!r.ok || data.success === false) {
                        const detail = data.debug_detail ? '\n\n' + data.debug_detail : '';
                        const msg422 = data.errors && typeof data.errors === 'object'
                            ? Object.values(data.errors).flat().join('\n')
                            : '';
                        console.error('[tokens/checkout]', r.status, data);
                        alert((data.error || data.message || msg422 || 'Erro ao processar.') + detail);
                        return;
                    }
                    if (data.success) {
                        paymentId = data.payment_id || null;
                        if (data.order_id) {
                            orderId = data.order_id;
                        }
                        const mode = data.checkout_mode || (data.is_pix ? 'pix' : null);

                        if (data.is_paid) {
                            setPaymentConfirmedUi();
                            return;
                        }

                        if (mode === 'pix' && data.pix_info) {
                            if (data.pix_info.encodedImage) {
                                pixQrcode.src = 'data:image/png;base64,' + data.pix_info.encodedImage;
                                pixQrcode.classList.remove('hidden');
                            }
                            pixCode.value = data.pix_info.payload || data.pix_info.copyPaste || '';
                            showModal(pixModal);
                            pollingInterval = setInterval(checkPaymentStatus, 5000);
                        } else if (mode === 'boleto') {
                            if (boletoMessage) boletoMessage.textContent = data.message || '';
                            if (boletoDueDate) {
                                if (data.due_date) {
                                    boletoDueDate.textContent = 'Vencimento: ' + data.due_date;
                                    boletoDueDate.classList.remove('hidden');
                                } else {
                                    boletoDueDate.classList.add('hidden');
                                }
                            }
                            if (boletoBarcode) {
                                boletoBarcode.value = data.identification_field || '';
                            }
                            if (boletoPdfLink) {
                                if (data.bank_slip_url) {
                                    boletoPdfLink.href = data.bank_slip_url;
                                    boletoPdfLink.classList.remove('hidden');
                                } else {
                                    boletoPdfLink.classList.add('hidden');
                                }
                            }
                            if (boletoStatus) {
                                boletoStatus.textContent = 'Aguardando confirmação do pagamento…';
                                boletoStatus.classList.add('text-amber-900', 'bg-amber-50');
                                boletoStatus.classList.remove('text-emerald-900', 'bg-emerald-50');
                            }
                            showModal(boletoModal);
                            pollingInterval = setInterval(checkPaymentStatus, 5000);
                        } else if (mode === 'card_confirmed') {
                            if (cardMessage) cardMessage.textContent = data.message || 'Pagamento confirmado.';
                            if (cardModalTitle) cardModalTitle.textContent = 'Pagamento confirmado';
                            if (cardSecondaryLink) cardSecondaryLink.classList.add('hidden');
                            markStatusPaid(cardStatus);
                            showModal(cardModal);
                            setTimeout(setPaymentConfirmedUi, 1500);
                        } else if (mode === 'card_pending') {
                            if (cardMessage) cardMessage.textContent = data.message || '';
                            if (cardModalTitle) cardModalTitle.textContent = 'Pagamento com cartão';
                            if (cardSecondaryLink) {
                                if (data.secondary_url) {
                                    cardSecondaryLink.href = data.secondary_url;
                                    cardSecondaryLink.textContent = data.secondary_url_label || 'Concluir verificação';
                                    cardSecondaryLink.classList.remove('hidden');
                                } else {
                                    cardSecondaryLink.classList.add('hidden');
                                }
                            }
                            if (cardStatus) {
                                cardStatus.textContent = 'Aguardando confirmação…';
                                cardStatus.classList.add('text-amber-900', 'bg-amber-50');
                                cardStatus.classList.remove('text-emerald-900', 'bg-emerald-50');
                            }
                            showModal(cardModal);
                            pollingInterval = setInterval(checkPaymentStatus, 5000);
                        } else if (paymentId) {
                            pollingInterval = setInterval(checkPaymentStatus, 5000);
                        } else {
                            window.location.href = '{{ route('career-trail.index') }}';
                        }
                    }
                }).catch(() => alert('Erro de rede.'))
                  .finally(() => {
                    resetCheckoutButtonState();
                  });
            });

            const cepInput = document.getElementById('cep');
            const cepFeedback = document.getElementById('cep-feedback');

            function setCepCheckoutFeedback(text, variant) {
                if (!cepFeedback) return;
                if (!text) {
                    cepFeedback.textContent = '';
                    cepFeedback.classList.add('hidden');
                    cepFeedback.classList.remove('text-red-600', 'text-emerald-700', 'text-gray-600');
                    return;
                }
                cepFeedback.textContent = text;
                cepFeedback.classList.remove('hidden');
                cepFeedback.classList.remove('text-red-600', 'text-emerald-700', 'text-gray-600');
                if (variant === 'error') cepFeedback.classList.add('text-red-600');
                else if (variant === 'ok') cepFeedback.classList.add('text-emerald-700');
                else cepFeedback.classList.add('text-gray-600');
            }

            function validateCepForCheckout() {
                if (!cepInput || typeof window.BrazilCep === 'undefined') return true;
                cepInput.value = window.BrazilCep.formatCepMasked(cepInput.value);
                const r = window.BrazilCep.validateBrazilCepField(cepInput.value);
                if (!r.ok) {
                    cepInput.classList.add('border-red-500');
                    setCepCheckoutFeedback(r.message, 'error');
                    alert(r.message);
                    cepInput.focus();
                    return false;
                }
                cepInput.classList.remove('border-red-500');
                return true;
            }

            if (cepInput && typeof window.BrazilCep !== 'undefined') {
                cepInput.addEventListener('blur', async function () {
                    cepInput.value = window.BrazilCep.formatCepMasked(cepInput.value);
                    if (!cepInput.value.trim()) {
                        setCepCheckoutFeedback('', null);
                        cepInput.classList.remove('border-red-500');
                        return;
                    }
                    const r = window.BrazilCep.validateBrazilCepField(cepInput.value);
                    if (!r.ok) {
                        cepInput.classList.add('border-red-500');
                        setCepCheckoutFeedback(r.message, 'error');
                        return;
                    }
                    cepInput.classList.remove('border-red-500');
                    try {
                        const data = await window.BrazilCep.fetchViaCep(r.digits);
                        window.BrazilCep.applyViaCepToForm(data, { address: 'address', city: 'city', state: 'state' });
                        setCepCheckoutFeedback('Endereço preenchido automaticamente pelo CEP.', 'ok');
                    } catch (e) {
                        setCepCheckoutFeedback(e.message || 'Erro ao buscar CEP.', 'error');
                    }
                });
            }
        });
    </script>
</x-app-layout>
