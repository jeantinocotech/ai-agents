<x-app-layout>
    <div class="max-w-2xl mx-auto py-10 px-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm ring-1 ring-slate-100">
            @if ($order->status === \App\Models\TokenPackOrder::STATUS_COMPLETED)
                <div class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 text-center">Compra confirmada</h1>
                <p class="mt-3 text-center text-slate-600">
                    {{ number_format((int) $order->tokens_amount, 0, ',', '.') }} tokens foram creditados na sua conta.
                </p>
            @elseif ($order->status === \App\Models\TokenPackOrder::STATUS_FAILED)
                <h1 class="text-2xl font-bold text-red-900 text-center">Pagamento não concluído</h1>
                <p class="mt-3 text-center text-slate-600">
                    Não foi possível confirmar esta compra. Pode tentar novamente ou consultar o histórico.
                </p>
            @else
                <h1 class="text-2xl font-bold text-amber-900 text-center">Pagamento em processamento</h1>
                <p class="mt-3 text-center text-slate-600">
                    Ainda estamos à espera da confirmação do {{ strtolower($order->paymentMethodLabel()) }}.
                    Os tokens serão creditados automaticamente quando o pagamento for confirmado.
                </p>
            @endif

            <dl class="mt-8 space-y-3 rounded-xl border border-slate-100 bg-slate-50/80 p-5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Pacote</dt>
                    <dd class="font-semibold text-slate-900 tabular-nums">{{ number_format((int) $order->tokens_amount, 0, ',', '.') }} tokens</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Valor pago</dt>
                    <dd class="font-semibold text-slate-900">R$ {{ number_format((float) $order->amount_brl, 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Método</dt>
                    <dd class="font-medium text-slate-900">{{ $order->paymentMethodLabel() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Estado</dt>
                    <dd class="font-medium text-slate-900">{{ $order->statusLabel() }}</dd>
                </div>
                @if ($order->status === \App\Models\TokenPackOrder::STATUS_COMPLETED)
                    <div class="flex justify-between gap-4 border-t border-slate-200 pt-3">
                        <dt class="text-slate-500">Saldo atual</dt>
                        <dd class="font-bold text-violet-700 tabular-nums">{{ number_format($balance, 0, ',', '.') }} tokens</dd>
                    </div>
                @endif
            </dl>

            @if ($order->isPending() && $order->payment_method === \App\Models\TokenPackOrder::PAYMENT_BOLETO && $order->bank_slip_url)
                <a href="{{ $order->bank_slip_url }}" target="_blank" rel="noopener noreferrer"
                   class="mt-6 inline-flex w-full justify-center rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-100">
                    Baixar PDF do boleto
                </a>
            @endif

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ $order->status === \App\Models\TokenPackOrder::STATUS_COMPLETED ? route('career-trail.index', ['compra' => 'ok', 'tokens' => (int) $order->tokens_amount]) : route('career-trail.index') }}"
                   class="inline-flex flex-1 justify-center rounded-lg bg-violet-600 px-4 py-3 text-sm font-semibold text-white hover:bg-violet-700">
                    Ir para a trilha
                </a>
                <a href="{{ route('tokens.history') }}"
                   class="inline-flex flex-1 justify-center rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 hover:bg-slate-50">
                    Ver histórico
                </a>
            </div>

            @if ($order->status !== \App\Models\TokenPackOrder::STATUS_COMPLETED)
                <p class="mt-6 text-center text-xs text-slate-500">
                    Também pode consultar o
                    <a href="{{ route('tokens.history') }}" class="text-violet-700 hover:underline">histórico de compras</a>
                    ou voltar ao
                    <a href="{{ route('tokens.purchase') }}" class="text-violet-700 hover:underline">checkout</a>.
                </p>
            @endif
        </div>
    </div>
</x-app-layout>
