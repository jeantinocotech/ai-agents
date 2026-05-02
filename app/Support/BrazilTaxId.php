<?php

namespace App\Support;

final class BrazilTaxId
{
    /** @return string Apenas dígitos */
    public static function onlyDigits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    public static function isValidCpf(string $digits): bool
    {
        if (strlen($digits) !== 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $digits[$i] * (($t + 1) - $i);
            }
            $rem = ($sum * 10) % 11 % 10;
            if ($rem !== (int) $digits[$t]) {
                return false;
            }
        }

        return true;
    }

    public static function isValidCnpj(string $digits): bool
    {
        if (strlen($digits) !== 14) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $digits)) {
            return false;
        }

        $w1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $digits[$i] * $w1[$i];
        }
        $r = $sum % 11;
        $d1 = $r < 2 ? 0 : 11 - $r;
        if ($d1 !== (int) $digits[12]) {
            return false;
        }

        $w2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $digits[$i] * $w2[$i];
        }
        $r = $sum % 11;
        $d2 = $r < 2 ? 0 : 11 - $r;

        return $d2 === (int) $digits[13];
    }

    public static function isValidCpfOrCnpj(string $digits): bool
    {
        $len = strlen($digits);
        if ($len === 11) {
            return self::isValidCpf($digits);
        }
        if ($len === 14) {
            return self::isValidCnpj($digits);
        }

        return false;
    }
}
