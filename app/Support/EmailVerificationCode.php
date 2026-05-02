<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class EmailVerificationCode
{
    public const CACHE_PREFIX = 'email_verify_code:';

    public const TTL_MINUTES = 60;

    /** @return array{hash: string, expires_at: int} */
    public static function remember(int $userId, string $plainSixDigits): void
    {
        $payload = [
            'hash' => hash('sha256', $plainSixDigits),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES)->getTimestamp(),
        ];

        Cache::put(self::CACHE_PREFIX.$userId, $payload, now()->addMinutes(self::TTL_MINUTES));
    }

    /** @return array{hash: string, expires_at: int}|null */
    public static function get(int $userId): ?array
    {
        $v = Cache::get(self::CACHE_PREFIX.$userId);

        return is_array($v) ? $v : null;
    }

    public static function forget(int $userId): void
    {
        Cache::forget(self::CACHE_PREFIX.$userId);
    }

    public static function isValid(int $userId, string $plainDigits): bool
    {
        $payload = self::get($userId);
        if ($payload === null || ! isset($payload['hash'], $payload['expires_at'])) {
            return false;
        }

        if (now()->getTimestamp() > (int) $payload['expires_at']) {
            self::forget($userId);

            return false;
        }

        return hash_equals($payload['hash'], hash('sha256', $plainDigits));
    }
}
