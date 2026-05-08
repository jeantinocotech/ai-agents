@props([
    'showOptOutLine' => true,
])

@php
    $welcome = max(0, (int) \App\Models\Setting::get('tokens_welcome_amount', 0));
    $intervalDays = max(1, (int) \App\Models\Setting::get('tokens_renewal_interval_days', 30));
@endphp

<div {{ $attributes->class(['rounded-xl border border-slate-200 bg-white/80 p-4 text-sm text-slate-700 shadow-sm ring-1 ring-slate-100']) }}>
    <p>
        Você ganha <strong class="text-slate-900">{{ number_format($welcome, 0, ',', '.') }} tokens</strong> para testar.
        A cada <strong class="text-slate-900">{{ $intervalDays }} dias</strong>, renovamos para <strong class="text-slate-900">{{ number_format($welcome, 0, ',', '.') }}</strong>.
        Se você decidir comprar tokens, o acesso passa a ser via compra de pacotes.
    </p>
    @if ($showOptOutLine)
        <p class="mt-2 text-xs text-slate-600">
            Após a primeira compra, as renovações grátis deixam de acontecer.
        </p>
    @endif
</div>

