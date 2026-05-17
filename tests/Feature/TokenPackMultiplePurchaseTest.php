<?php

use App\Models\Setting;
use App\Models\TokenPackOrder;
use App\Models\User;
use App\Services\AsaasService;

test('token pack checkout supports buying multiple packs', function () {
    Setting::set('token_pack_amount', '1000');
    Setting::set('token_pack_price', '30.00');

    /** @var User $user */
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->mock(AsaasService::class, function ($mock) {
        $mock->shouldReceive('findCustomerByExternalReference')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn(['id' => 'cus_test']);
        $mock->shouldReceive('createPayment')->andReturn([
            'id' => 'pay_test',
            'status' => 'PENDING',
            'dueDate' => '2026-05-20',
            'identificationField' => '23793.38128 60000.000003 00000.000400 1 99990000030000',
            'bankSlipUrl' => 'https://asaas.example/boleto.pdf',
        ]);
    });

    $payload = [
        'quantity' => 3,
        'name' => 'Teste',
        'email' => 't@e.com',
        'phone' => '11999999999',
        'document' => '529.982.247-25',
        'cep' => '01001-000',
        'address' => 'Rua X',
        'number' => '10',
        'city' => 'São Paulo',
        'state' => 'SP',
        'payment_method' => 'boleto',
    ];

    $this->actingAs($user)
        ->postJson(route('tokens.purchase.process'), $payload)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('payment_id', 'pay_test')
        ->assertJsonPath('checkout_mode', 'boleto')
        ->assertJsonPath('identification_field', '23793.38128 60000.000003 00000.000400 1 99990000030000')
        ->assertJsonPath('bank_slip_url', 'https://asaas.example/boleto.pdf')
        ->assertJsonMissing(['invoice_url'])
        ->assertJsonStructure(['order_id']);

    $order = TokenPackOrder::query()->where('user_id', $user->id)->latest('id')->first();
    expect($order)->not->toBeNull();
    expect((int) $order->tokens_amount)->toBe(3000);
    expect((float) $order->amount_brl)->toBe(90.00);
});

test('token pack checkout allows quantities above 10 in steps of 5', function () {
    Setting::set('token_pack_amount', '1000');
    Setting::set('token_pack_price', '30.00');

    /** @var User $user */
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->mock(AsaasService::class, function ($mock) {
        $mock->shouldReceive('findCustomerByExternalReference')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn(['id' => 'cus_test']);
        $mock->shouldReceive('createPayment')->andReturn([
            'id' => 'pay_test',
            'status' => 'PENDING',
            'identificationField' => '12345',
        ]);
    });

    $payload = [
        'quantity' => 15,
        'name' => 'Teste',
        'email' => 't@e.com',
        'phone' => '11999999999',
        'document' => '529.982.247-25',
        'cep' => '01001-000',
        'address' => 'Rua X',
        'number' => '10',
        'city' => 'São Paulo',
        'state' => 'SP',
        'payment_method' => 'boleto',
    ];

    $this->actingAs($user)
        ->postJson(route('tokens.purchase.process'), $payload)
        ->assertOk()
        ->assertJsonPath('success', true);

    $order = TokenPackOrder::query()->where('user_id', $user->id)->latest('id')->first();
    expect($order)->not->toBeNull();
    expect((int) $order->tokens_amount)->toBe(15000);
    expect((float) $order->amount_brl)->toBe(450.00);
});

function tokenPackCheckoutPayload(array $overrides = []): array
{
    return array_merge([
        'quantity' => 1,
        'name' => 'Teste',
        'email' => 't@e.com',
        'phone' => '11999999999',
        'document' => '529.982.247-25',
        'cep' => '01001-000',
        'address' => 'Rua X',
        'number' => '10',
        'city' => 'São Paulo',
        'state' => 'SP',
    ], $overrides);
}

test('token pack checkout returns card_confirmed when payment is confirmed', function () {
    Setting::set('token_pack_amount', '1000');
    Setting::set('token_pack_price', '30.00');

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->mock(AsaasService::class, function ($mock) {
        $mock->shouldReceive('findCustomerByExternalReference')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn(['id' => 'cus_test']);
        $mock->shouldReceive('createPayment')->andReturn([
            'id' => 'pay_card_ok',
            'status' => 'CONFIRMED',
        ]);
    });

    $this->actingAs($user)
        ->postJson(route('tokens.purchase.process'), tokenPackCheckoutPayload([
            'payment_method' => 'credit_card',
            'card_number' => '4111111111111111',
            'card_expiry' => '12/30',
            'card_cvv' => '123',
            'card_holder_name' => 'Teste User',
        ]))
        ->assertOk()
        ->assertJsonPath('checkout_mode', 'card_confirmed')
        ->assertJsonPath('is_paid', true)
        ->assertJsonMissing(['invoice_url', 'secondary_url']);
});

test('token pack checkout returns card_pending with optional secondary url', function () {
    Setting::set('token_pack_amount', '1000');
    Setting::set('token_pack_price', '30.00');

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->mock(AsaasService::class, function ($mock) {
        $mock->shouldReceive('findCustomerByExternalReference')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn(['id' => 'cus_test']);
        $mock->shouldReceive('createPayment')->andReturn([
            'id' => 'pay_card_pending',
            'status' => 'PENDING',
            'invoiceUrl' => 'https://asaas.example/3ds',
        ]);
    });

    $this->actingAs($user)
        ->postJson(route('tokens.purchase.process'), tokenPackCheckoutPayload([
            'payment_method' => 'credit_card',
            'card_number' => '4111111111111111',
            'card_expiry' => '12/30',
            'card_cvv' => '123',
            'card_holder_name' => 'Teste User',
        ]))
        ->assertOk()
        ->assertJsonPath('checkout_mode', 'card_pending')
        ->assertJsonPath('is_paid', false)
        ->assertJsonPath('secondary_url', 'https://asaas.example/3ds')
        ->assertJsonMissing(['invoice_url']);
});

test('token pack checkout returns pix mode with qr payload', function () {
    Setting::set('token_pack_amount', '1000');
    Setting::set('token_pack_price', '30.00');

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->mock(AsaasService::class, function ($mock) {
        $mock->shouldReceive('findCustomerByExternalReference')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn(['id' => 'cus_test']);
        $mock->shouldReceive('createPayment')->andReturn([
            'id' => 'pay_pix',
            'status' => 'PENDING',
        ]);
        $mock->shouldReceive('getPixQrCode')->with('pay_pix')->andReturn([
            'encodedImage' => base64_encode('fake'),
            'payload' => '00020126330014BR.GOV.BCB.PIX',
        ]);
    });

    $this->actingAs($user)
        ->postJson(route('tokens.purchase.process'), tokenPackCheckoutPayload([
            'payment_method' => 'pix',
        ]))
        ->assertOk()
        ->assertJsonPath('checkout_mode', 'pix')
        ->assertJsonPath('is_pix', true)
        ->assertJsonPath('pix_info.payload', '00020126330014BR.GOV.BCB.PIX');
});

