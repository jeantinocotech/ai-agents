@props([
    'event',
    'params' => [],
])

@if(\App\Support\GoogleAnalytics::enabled())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof window.gratoGaEvent === 'function') {
                    window.gratoGaEvent(@json($event), @json($params));
                }
            });
        </script>
    @endpush
@endif
