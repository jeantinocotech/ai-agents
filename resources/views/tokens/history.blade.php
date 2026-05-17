<x-app-layout>
    <div class="max-w-5xl mx-auto py-10 px-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Histórico de compras de tokens</h1>
                <p class="text-gray-600 mt-1 text-sm">Pacotes adquiridos no GratoAI</p>
            </div>
            <a href="{{ route('tokens.purchase') }}"
               class="inline-flex justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                Nova compra
            </a>
        </div>

        <div class="bg-white shadow-sm border rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-4 py-3 font-medium">Data</th>
                            <th class="px-4 py-3 font-medium">Tokens</th>
                            <th class="px-4 py-3 font-medium">Valor</th>
                            <th class="px-4 py-3 font-medium">Método</th>
                            <th class="px-4 py-3 font-medium">Estado</th>
                            <th class="px-4 py-3 font-medium">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-800">
                                    {{ $order->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3">{{ number_format((int) $order->tokens_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $order->amount_brl, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $order->paymentMethodLabel() }}</td>
                                <td class="px-4 py-3">
                                    @if ($order->status === \App\Models\TokenPackOrder::STATUS_COMPLETED)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">{{ $order->statusLabel() }}</span>
                                    @elseif ($order->status === \App\Models\TokenPackOrder::STATUS_FAILED)
                                        <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">{{ $order->statusLabel() }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900">{{ $order->statusLabel() }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($order->status === \App\Models\TokenPackOrder::STATUS_COMPLETED)
                                        <a href="{{ route('tokens.thank-you', $order) }}" class="text-violet-700 hover:underline text-xs font-medium">Ver resumo</a>
                                    @elseif ($order->isPending() && $order->payment_method === \App\Models\TokenPackOrder::PAYMENT_BOLETO && $order->bank_slip_url)
                                        <a href="{{ $order->bank_slip_url }}" target="_blank" rel="noopener noreferrer" class="text-violet-700 hover:underline text-xs font-medium">Baixar boleto</a>
                                    @elseif ($order->isPending())
                                        <a href="{{ route('tokens.purchase') }}" class="text-slate-600 hover:underline text-xs font-medium">Continuar pagamento</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    Ainda não há pedidos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($orders->hasPages())
            <div class="mt-6">
                {{ $orders->links() }}
            </div>
        @endif

        <p class="mt-6 text-xs text-gray-500">
            O saldo creditado também aparece como transação do tipo compra no modelo interno de tokens.
            Boleto pode demorar a compensar; quando o pagamento for confirmado, o crédito ocorre automaticamente.
        </p>
    </div>
</x-app-layout>
