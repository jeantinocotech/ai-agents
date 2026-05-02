/**
 * Validação de CPF/CNPJ igual a App\Support\BrazilTaxId (só dígitos; 11 ou 14).
 */
export function onlyDigits(value) {
    return String(value ?? '').replace(/\D/g, '');
}

export function isValidCpf(digits) {
    if (digits.length !== 11) {
        return false;
    }

    if (/^(\d)\1{10}$/.test(digits)) {
        return false;
    }

    for (let t = 9; t < 11; t += 1) {
        let sum = 0;
        for (let i = 0; i < t; i += 1) {
            sum += Number.parseInt(digits[i], 10) * ((t + 1) - i);
        }
        const rem = (sum * 10) % 11 % 10;
        if (rem !== Number.parseInt(digits[t], 10)) {
            return false;
        }
    }

    return true;
}

export function isValidCnpj(digits) {
    if (digits.length !== 14) {
        return false;
    }

    if (/^(\d)\1{13}$/.test(digits)) {
        return false;
    }

    const w1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    let sum = 0;
    for (let i = 0; i < 12; i += 1) {
        sum += Number.parseInt(digits[i], 10) * w1[i];
    }
    const r1 = sum % 11;
    const d1 = r1 < 2 ? 0 : 11 - r1;
    if (d1 !== Number.parseInt(digits[12], 10)) {
        return false;
    }

    const w2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    sum = 0;
    for (let i = 0; i < 13; i += 1) {
        sum += Number.parseInt(digits[i], 10) * w2[i];
    }
    const r2 = sum % 11;
    const d2 = r2 < 2 ? 0 : 11 - r2;

    return d2 === Number.parseInt(digits[13], 10);
}

export function isValidCpfOrCnpj(digits) {
    const len = digits.length;
    if (len === 11) {
        return isValidCpf(digits);
    }
    if (len === 14) {
        return isValidCnpj(digits);
    }
    return false;
}

export function validateBrazilTaxIdField(raw) {
    const digits = onlyDigits(raw);
    if (digits.length === 0) {
        return { ok: false, message: 'Informe o CPF ou CNPJ.' };
    }
    if (digits.length !== 11 && digits.length !== 14) {
        return { ok: false, message: 'CPF deve ter 11 dígitos ou CNPJ 14 dígitos.' };
    }
    if (! isValidCpfOrCnpj(digits)) {
        return { ok: false, message: 'CPF ou CNPJ inválido. Verifique os dígitos.' };
    }

    return { ok: true, digits };
}

if (typeof window !== 'undefined') {
    window.BrazilTaxId = {
        onlyDigits,
        isValidCpf,
        isValidCnpj,
        isValidCpfOrCnpj,
        validateBrazilTaxIdField,
    };
}
