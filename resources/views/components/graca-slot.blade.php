{{-- Corpo(s) da Graça: mensagens na BD (placement + passo); só se não existir nenhuma se usa `fallback`. --}}
@props([
    'placement' => '',
    'processKey' => 'career_trail',
    'step' => null,
    'fallback' => null,
    'minCharsPlaceholder' => null,
    'paragraphClass' => 'mt-2 text-sm leading-relaxed text-slate-600',
    'tag' => 'p',
])

@php
    $stepId = $step instanceof \App\Models\CareerTrailStep ? (int) $step->getKey() : null;
    $bodies = \App\Services\CareerTrailGracaMessageService::bodies($processKey, $stepId, $placement);
@endphp

@if ($bodies->isNotEmpty())
    @foreach ($bodies as $body)
        @php
            $out = $body;
            if ($minCharsPlaceholder !== null && $minCharsPlaceholder !== '') {
                $out = str_replace('__MIN_CHARS__', (string) $minCharsPlaceholder, $out);
            }
            $t = in_array($tag, ['p', 'div'], true) ? $tag : 'p';
        @endphp
        <{{ $t }} {{ $attributes->merge(['class' => $paragraphClass]) }}>{!! nl2br(e($out)) !!}</{{ $t }}>
    @endforeach
@elseif ($fallback !== null && trim((string) $fallback) !== '')
    @php
        $out = (string) $fallback;
        if ($minCharsPlaceholder !== null && $minCharsPlaceholder !== '') {
            $out = str_replace('__MIN_CHARS__', (string) $minCharsPlaceholder, $out);
        }
        $t = in_array($tag, ['p', 'div'], true) ? $tag : 'p';
    @endphp
    <{{ $t }} {{ $attributes->merge(['class' => $paragraphClass]) }}>{!! nl2br(e($out)) !!}</{{ $t }}>
@endif
