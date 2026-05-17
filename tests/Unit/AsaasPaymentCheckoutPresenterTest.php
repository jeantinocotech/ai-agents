<?php

use App\Support\AsaasPaymentCheckoutPresenter;

test('presenter marks paid statuses', function () {
    expect(AsaasPaymentCheckoutPresenter::isPaid('CONFIRMED'))->toBeTrue();
    expect(AsaasPaymentCheckoutPresenter::isPaid('RECEIVED'))->toBeTrue();
    expect(AsaasPaymentCheckoutPresenter::isPaid('PENDING'))->toBeFalse();
});

test('presenter builds boleto checkout payload', function () {
    $payload = AsaasPaymentCheckoutPresenter::presentForCheckout([
        'id' => 'pay_1',
        'status' => 'PENDING',
        'dueDate' => '2026-05-20',
        'identificationField' => '12345',
        'bankSlipUrl' => 'https://example.com/boleto.pdf',
    ], 'boleto');

    expect($payload['checkout_mode'])->toBe('boleto');
    expect($payload['identification_field'])->toBe('12345');
    expect($payload['bank_slip_url'])->toBe('https://example.com/boleto.pdf');
});
