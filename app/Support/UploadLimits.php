<?php

namespace App\Support;

/**
 * Limites efetivos de upload (app vs php.ini).
 */
final class UploadLimits
{
    /** Limite de negócio para foto de perfil (5 MB). */
    public const PROFILE_PHOTO_APP_MAX_BYTES = 5 * 1024 * 1024;

    public static function profilePhotoMaxBytes(): int
    {
        $phpUpload = self::iniSizeToBytes((string) ini_get('upload_max_filesize'));
        $phpPost = self::iniSizeToBytes((string) ini_get('post_max_size'));

        // Margem para os restantes campos do formulário de perfil.
        $postBudget = max(0, $phpPost - (512 * 1024));

        return min(self::PROFILE_PHOTO_APP_MAX_BYTES, $phpUpload, $postBudget);
    }

    public static function profilePhotoMaxLabel(): string
    {
        return self::formatBytes(self::profilePhotoMaxBytes());
    }

    public static function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        if (ctype_alpha($unit)) {
            $number = (float) substr($value, 0, -1);
        } else {
            $unit = '';
        }

        return (int) round(match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        });
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            $mb = $bytes / (1024 * 1024);

            return (abs($mb - round($mb)) < 0.05)
                ? ((int) round($mb)).' MB'
                : number_format($mb, 1, ',', '.').' MB';
        }

        if ($bytes >= 1024) {
            return (int) round($bytes / 1024).' KB';
        }

        return $bytes.' bytes';
    }
}
