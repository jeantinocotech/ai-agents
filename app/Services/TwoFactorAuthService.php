<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

final class TwoFactorAuthService
{
    public function google2fa(): Google2FA
    {
        return new Google2FA;
    }

    public function needsChallenge(User $user): bool
    {
        return $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at !== null;
    }

    public function qrCodeUrl(User $user, string $secret): string
    {
        $appName = config('app.name', 'App');

        return $this->google2fa()->getQRCodeUrl($appName, (string) $user->email, $secret);
    }

    public function verifyCode(User $user, string $code): bool
    {
        if ($user->two_factor_secret === null) {
            return false;
        }

        return $this->google2fa()->verifyKey($user->two_factor_secret, trim($code), 2);
    }

    /**
     * @return list<string> Códigos em texto claro (mostrar uma única vez ao utilizador).
     */
    public function generateRecoveryPlainCodes(int $count = 8): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = Str::upper(Str::random(10));
        }

        return $out;
    }

    /**
     * @param  list<string>  $plainCodes
     * @return list<string> Hashes para gravar em JSON
     */
    public function hashRecoveryCodes(array $plainCodes): array
    {
        return array_map(static fn (string $c) => Hash::make(trim($c)), $plainCodes);
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        /** @var list<string> $hashes */
        $hashes = $user->two_factor_recovery_hashes ?? [];
        if ($hashes === []) {
            return false;
        }

        $code = trim($code);
        $remaining = [];
        $matched = false;

        foreach ($hashes as $h) {
            if (! $matched && Hash::check($code, $h)) {
                $matched = true;

                continue;
            }
            $remaining[] = $h;
        }

        if (! $matched) {
            return false;
        }

        $user->two_factor_recovery_hashes = $remaining === [] ? null : $remaining;
        $user->save();

        return true;
    }

    public function confirmAndEnable(User $user, string $plainSecret, array $plainRecoveryCodes): void
    {
        $user->two_factor_secret = $plainSecret;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_hashes = $this->hashRecoveryCodes($plainRecoveryCodes);
        $user->save();
    }

    public function disable(User $user): void
    {
        $user->two_factor_secret = null;
        $user->two_factor_recovery_hashes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();
    }
}
