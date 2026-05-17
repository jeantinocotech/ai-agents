<?php

use App\Models\Setting;
use App\Models\TokenPackOrder;
use App\Models\User;
use App\Services\AsaasService;

test('thank you page is accessible to order owner when completed', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'token_balance' => 5000]);
    $order = TokenPackOrder::query()->create([
        'user_id' => $user->id,
        'tokens_amount' => 1000,
        'amount_brl' => 30,
        'status' => TokenPackOrder::STATUS_COMPLETED,
        'payment_method' => TokenPackOrder::PAYMENT_PIX,
    ]);

    $this->actingAs($user)
        ->get(route('tokens.thank-you', $order))
        ->assertOk()
        ->assertSee('Compra confirmada')
        ->assertSee('5.000');
});

test('thank you page is forbidden for other users', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $other = User::factory()->create(['email_verified_at' => now()]);
    $order = TokenPackOrder::query()->create([
        'user_id' => $owner->id,
        'tokens_amount' => 1000,
        'amount_brl' => 30,
        'status' => TokenPackOrder::STATUS_COMPLETED,
        'payment_method' => TokenPackOrder::PAYMENT_PIX,
    ]);

    $this->actingAs($other)
        ->get(route('tokens.thank-you', $order))
        ->assertForbidden();
});

test('checkout json includes order id and payment status includes order id', function () {
    Setting::set('token_pack_amount', '1000');
    Setting::set('token_pack_price', '30.00');

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->mock(AsaasService::class, function ($mock) {
        $mock->shouldReceive('findCustomerByExternalReference')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn(['id' => 'cus_test']);
        $mock->shouldReceive('createPayment')->andReturn([
            'id' => 'pay_status_test',
            'status' => 'CONFIRMED',
        ]);
    });

    $response = $this->actingAs($user)->postJson(route('tokens.purchase.process'), [
        'name' => 'Teste',
        'email' => 't@e.com',
        'phone' => '11999999999',
        'document' => '529.982.247-25',
        'cep' => '01001-000',
        'address' => 'Rua X',
        'number' => '10',
        'city' => 'São Paulo',
        'state' => 'SP',
        'payment_method' => 'credit_card',
        'card_number' => '4111111111111111',
        'card_expiry' => '12/30',
        'card_cvv' => '123',
        'card_holder_name' => 'Teste User',
    ]);

    $orderId = $response->json('order_id');
    expect($orderId)->toBeInt();

    $this->mock(AsaasService::class, function ($mock) use ($user, $orderId) {
        $mock->shouldReceive('getPayment')->with('pay_status_test')->andReturn([
            'id' => 'pay_status_test',
            'status' => 'CONFIRMED',
            'externalReference' => json_encode([
                'type' => 'token_pack',
                'order_id' => $orderId,
                'user_id' => $user->id,
            ]),
        ]);
    });

    $this->actingAs($user)
        ->postJson(route('tokens.payment.status'), ['payment_id' => 'pay_status_test'])
        ->assertOk()
        ->assertJsonPath('order_id', $orderId)
        ->assertJsonPath('is_paid', true);
});
