<?php

namespace App\Support;

final class LogAlertMailRecipients
{
    /**
     * @return list<string>
     */
    public static function parse(?string $raw = null): array
    {
        $raw ??= (string) config('services.chatkit.alert_mail_raw', '');

        if (trim($raw) === '') {
            return [];
        }

        $emails = [];
        foreach (preg_split('/\s*,\s*/', $raw) as $part) {
            $email = strtolower(trim($part));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }
}
