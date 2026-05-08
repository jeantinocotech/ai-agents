@props([
    'showOptOutLine' => true,
])

@php
    $welcome = max(0, (int) \App\Models\Setting::get('tokens_welcome_amount', 0));
    $intervalDays = max(1, (int) \App\Models\Setting::get('tokens_renewal_interval_days', 30));
    $packAmount = max(0, (int) \App\Models\Setting::get('token_pack_amount', 0));
    $packPrice = (float) \App\Models\Setting::get('token_pack_price', 0);
@endphp

<div {{ $attributes->class(['rounded-xl border border-slate-200 bg-white/80 p-4 text-sm text-slate-700 shadow-sm ring-1 ring-slate-100']) }}>
    <p class="font-semibold text-slate-900">Condição Especial de Lançamento!</p>
    <p class="mt-2">
        Cadastre-se agora e ganhe <strong class="text-slate-900">{{ number_format($welcome, 0, ',', '.') }} tokens</strong> para testar todas as funcionalidades.
        Após <strong class="text-slate-900">{{ $intervalDays }} dias</strong>, renovamos gratuitamente seus tokens para <strong class="text-slate-900">{{ number_format($welcome, 0, ',', '.') }}</strong>.
        Se precisar de mais Tokens para seguir com seu desenvolvimento, você pode adquirir
        <strong class="text-slate-900">{{ number_format($packAmount, 0, ',', '.') }} tokens</strong>
        por apenas <strong class="text-slate-900">R$ {{ number_format($packPrice, 2, ',', '.') }}</strong>.
    </p>
    <p class="mt-2 text-xs text-slate-600">
        Após primeira compra a renovação grátis deixa de existir. Oportunidade por tempo limitado, aproveite.
    </p>
</div>

