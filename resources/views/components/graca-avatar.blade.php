@props([
    'size' => 'md',
])

@php
    $box = match ($size) {
        'sm' => 'h-10 w-10 min-h-[2.5rem] min-w-[2.5rem] rounded-xl',
        'md' => 'h-16 w-16 min-h-[4rem] min-w-[4rem] rounded-xl',
        'lg' => 'h-20 w-20 min-h-[5rem] min-w-[5rem] rounded-2xl',
        default => 'h-16 w-16 min-h-[4rem] min-w-[4rem] rounded-xl',
    };
    $alt = config('career_trail.mentor_label', 'Graça');
@endphp

<img
    src="{{ asset(config('career_trail.mentor_avatar')) }}"
    alt="Retrato de {{ $alt }}, guia da trilha de carreira"
    width="160"
    height="160"
    loading="lazy"
    decoding="async"
    {{ $attributes->class($box.' shrink-0 object-cover object-top') }}
/>
