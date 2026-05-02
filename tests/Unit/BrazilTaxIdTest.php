<?php

use App\Support\BrazilTaxId;

test('validates known valid CPF and CNPJ', function () {
    expect(BrazilTaxId::isValidCpfOrCnpj('39053344705'))->toBeTrue();

    expect(BrazilTaxId::isValidCpfOrCnpj('11222333000181'))->toBeTrue();
});

test('rejects invalid identifiers', function () {
    expect(BrazilTaxId::isValidCpfOrCnpj('12345678901'))->toBeFalse();

    expect(BrazilTaxId::isValidCpfOrCnpj('11222333000999'))->toBeFalse();

    expect(BrazilTaxId::isValidCpfOrCnpj(''))->toBeFalse();
});
