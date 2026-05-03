{{-- Ícones na aba: paths em config/branding.php ou .env (BRAND_FAVICON_*) --}}
@php
    $svg = trim((string) config('branding.favicon_svg', 'favicon.svg'));
    $ico = trim((string) config('branding.favicon_ico', 'favicon.ico'));
    $svgPath = $svg !== '' ? public_path($svg) : null;
    $icoPath = $ico !== '' ? public_path($ico) : null;
@endphp
@if ($svgPath && is_file($svgPath))
    <link rel="icon" href="{{ asset($svg) }}" type="image/svg+xml">
@endif
@if ($icoPath && is_file($icoPath))
    <link rel="icon" href="{{ asset($ico) }}" sizes="32x32" type="image/x-icon">
@endif
