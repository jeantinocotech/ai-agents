@props([
    'size' => 'md',
])

@php
    $box = match ($size) {
        'sm' => 'h-10 w-10 min-h-[2.5rem] min-w-[2.5rem]',
        'md' => 'h-12 w-12 min-h-[3rem] min-w-[3rem] sm:h-14 sm:w-14 sm:min-h-[3.5rem] sm:min-w-[3.5rem]',
        'lg' => 'h-20 w-20 min-h-[5rem] min-w-[5rem]',
        default => 'h-12 w-12 min-h-[3rem] min-w-[3rem]',
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
    {{ $attributes->class($box.' shrink-0 rounded-2xl object-cover object-top shadow-md ring-2 ring-white') }}
/>
