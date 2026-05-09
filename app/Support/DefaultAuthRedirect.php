<?php

namespace App\Support;

/**
 * URL usada como fallback em redirect()->intended(...) após login / 2FA / consentimento legal.
 */
final class DefaultAuthRedirect
{
    public static function url(): string
    {
        return route('career-trail.index', absolute: false);
    }
}
