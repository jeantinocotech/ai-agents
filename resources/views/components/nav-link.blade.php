@props(['active'])

@php
    $base = 'inline-flex items-center px-1 pt-1 text-sm font-semibold transition';
    $classes = ($active ?? false)
        ? $base . ' text-white'
        : $base . ' text-gray-300 hover:text-white focus:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
