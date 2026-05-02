/**
 * CEP brasileiro (8 dígitos) + consulta ViaCEP (API pública).
 */
export function cepOnlyDigits(value) {
    return String(value ?? '').replace(/\D/g, '').slice(0, 8);
}

export function formatCepMasked(value) {
    const d = cepOnlyDigits(value);
    if (d.length <= 5) return d;

    return `${d.slice(0, 5)}-${d.slice(5, 8)}`;
}

export function validateBrazilCepDigits(digits) {
    if (digits.length !== 8) {
        return { ok: false, message: 'O CEP deve ter 8 dígitos.', digits };
    }
    if (/^0{8}$/.test(digits)) {
        return { ok: false, message: 'CEP inválido.', digits };
    }

    return { ok: true, digits };
}

export function validateBrazilCepField(raw) {
    const digits = cepOnlyDigits(raw);

    return validateBrazilCepDigits(digits);
}

export async function fetchViaCep(cepDigits8) {
    const r = await fetch(`https://viacep.com.br/ws/${cepDigits8}/json/`, {
        credentials: 'omit',
        headers: { Accept: 'application/json' },
    });
    if (! r.ok) {
        throw new Error('Não foi possível consultar o CEP. Tente de novo.');
    }
    const data = await r.json();
    if (data.erro) {
        throw new Error('CEP não encontrado. Verifique os números.');
    }

    return data;
}

/**
 * @param {object} data resposta ViaCEP
 * @param {{ cep?: string, address: string, city: string, state: string, neighborhood?: string }} fieldIds ids dos inputs
 */
export function applyViaCepToForm(data, fieldIds) {
    const address = document.getElementById(fieldIds.address);
    const city = document.getElementById(fieldIds.city);
    const state = document.getElementById(fieldIds.state);
    if (address) {
        address.value = data.logradouro || '';
    }
    if (city) {
        city.value = data.localidade || '';
    }
    if (state) {
        state.value = (data.uf || '').toUpperCase().slice(0, 2);
    }
    if (fieldIds.neighborhood) {
        const b = document.getElementById(fieldIds.neighborhood);
        if (b && data.bairro) {
            b.value = data.bairro;
        }
    }
}

if (typeof window !== 'undefined') {
    window.BrazilCep = {
        cepOnlyDigits,
        formatCepMasked,
        validateBrazilCepField,
        validateBrazilCepDigits,
        fetchViaCep,
        applyViaCepToForm,
    };
}
