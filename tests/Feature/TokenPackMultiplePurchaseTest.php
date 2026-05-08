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
            'invoiceUrl' => 'https://asaas.example/invoice',
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
        ->assertJsonPath('payment_id', 'pay_test');

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
            'invoiceUrl' => 'https://asaas.example/invoice',
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

