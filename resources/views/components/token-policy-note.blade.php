@props([
    'showOptOutLine' => true,
])

@php
    $welcome = max(0, (int) \App\Models\Setting::get('tokens_welcome_amount', 0));
    $intervalDays = max(1, (int) \App\Models\Setting::get('tokens_renewal_interval_days', 30));
    $packAmount = max(0, (int) \App\Models\Setting::get('token_pack_amount', 0));
    $packPrice = (float) \App\Models\Setting::get('token_pack_price', 0);
@endphp

<div {{ $attributes->class(['rounded-2xl border border-teal-300 bg-teal-100 p-4 text-sm shadow-sm ring-1 ring-teal-200']) }}>
    <p class="font-semibold text-indigo-800">Condição Especial de Lançamento!</p>
    <p class="mt-2 text-slate-600">
        Cadastre-se agora e ganhe <strong class="font-semibold text-indigo-800">{{ number_format($welcome, 0, ',', '.') }} tokens</strong> para testar todas as funcionalidades.
        Após <strong class="font-semibold text-indigo-800">{{ $intervalDays }} dias</strong>, renovamos gratuitamente seus tokens para <strong class="font-semibold text-indigo-800">{{ number_format($welcome, 0, ',', '.') }}</strong>.
        Se precisar de mais Tokens para seguir com seu desenvolvimento, você pode adquirir
        <strong class="font-semibold text-indigo-800">{{ number_format($packAmount, 0, ',', '.') }} tokens</strong>
        por apenas <strong class="font-semibold text-indigo-800">R$ {{ number_format($packPrice, 2, ',', '.') }}</strong>.
    </p>
    <p class="mt-2 text-xs text-slate-500">
        Após primeira compra a renovação grátis deixa de existir. Oportunidade por tempo limitado, aproveite.
    </p>
</div>

