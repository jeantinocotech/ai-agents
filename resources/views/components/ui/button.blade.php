@props([
    'variant' => 'primary',
    'size' => 'sm', // xs | sm | md | field (h-10, alinhado a inputs)
    'href' => null,
    'type' => 'button',
])

@php
    $classes = \App\Support\UiButton::classes($variant, $size);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
