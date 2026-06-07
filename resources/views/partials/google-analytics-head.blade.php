@if(\App\Support\GoogleAnalytics::enabled())
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.measurement_id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', '{{ config('services.google_analytics.measurement_id') }}');

        window.gratoGaEvent = function (eventName, params) {
            if (typeof gtag !== 'function') {
                return;
            }

            gtag('event', eventName, params || {});
        };

        window.gratoGaTrack = function (eventName, params, destinationUrl) {
            if (typeof gtag !== 'function') {
                return true;
            }

            if (!destinationUrl) {
                window.gratoGaEvent(eventName, params);

                return false;
            }

            var navigated = false;
            var go = function () {
                if (navigated) {
                    return;
                }
                navigated = true;
                window.location.href = destinationUrl;
            };
            var payload = Object.assign({}, params || {}, {
                transport_type: 'beacon',
                link_url: destinationUrl,
                event_callback: go,
            });

            gtag('event', eventName, payload);
            setTimeout(go, 500);

            return false;
        };
    </script>
@endif
