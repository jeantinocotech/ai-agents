@if(\App\Support\GoogleAnalytics::enabled() && session(\App\Support\GoogleAnalytics::SESSION_KEY))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.gratoGaEvent !== 'function') {
                return;
            }

            @foreach (session(\App\Support\GoogleAnalytics::SESSION_KEY, []) as $gaEvent)
                window.gratoGaEvent(@json($gaEvent['name']), @json($gaEvent['params'] ?? []));
            @endforeach
        });
    </script>
@endif
