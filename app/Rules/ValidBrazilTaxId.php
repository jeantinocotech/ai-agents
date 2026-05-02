<?php

namespace App\Rules;

use App\Support\BrazilTaxId;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidBrazilTaxId implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $trimmed = trim((string) $value);
        $digits = BrazilTaxId::onlyDigits($trimmed);
        if ($digits === '') {
            if ($trimmed !== '') {
                $fail('Informe apenas números ou use a máscara de CPF/CNPJ.');
            }

            return;
        }

        if (! BrazilTaxId::isValidCpfOrCnpj($digits)) {
            $fail('Informe um CPF ou CNPJ válido (apenas números ou com máscara).');
        }
    }
}
