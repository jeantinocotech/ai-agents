<?php

namespace App\Support;

final class GoogleAnalytics
{
    public const SESSION_KEY = 'ga_events';

    public static function enabled(): bool
    {
        if (! config('services.google_analytics.measurement_id')) {
            return false;
        }

        if (request()->is('admin/*')) {
            return false;
        }

        $user = auth()->user();

        return ! ($user && $user->isAdmin());
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public static function flash(string $name, array $params = []): void
    {
        if (! self::enabled()) {
            return;
        }

        $events = session(self::SESSION_KEY, []);
        $events[] = ['name' => $name, 'params' => $params];
        session()->flash(self::SESSION_KEY, $events);
    }
}
