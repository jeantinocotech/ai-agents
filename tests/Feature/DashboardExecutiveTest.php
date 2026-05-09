<?php

use App\Models\User;

test('verified user can view executive dashboard', function () {
    $this->seed(\Database\Seeders\CareerTrailStepsSeeder::class);

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', absolute: false))
        ->assertOk()
        ->assertSee('Badges da trilha', false)
        ->assertSee('Aprovações', false)
        ->assertSee('Tokens', false);
});
