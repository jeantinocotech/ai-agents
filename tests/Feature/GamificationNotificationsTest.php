<?php

use App\Models\User;
use App\Notifications\GamificationMilestoneNotification;

test('authenticated user can fetch unread gamification notifications', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->notify(new GamificationMilestoneNotification([
        'kind' => 'badge_level_up',
        'title' => 'Progresso na trilha',
        'body' => 'CV: nível novo',
        'icon_key' => '📄',
        'url' => route('dashboard'),
    ]));

    $response = $this->actingAs($user)->getJson(route('notifications.gamification.unread'));

    $response->assertOk();
    $response->assertJsonPath('count', 1);
    $response->assertJsonCount(1, 'items');
    $response->assertJsonPath('items.0.data.title', 'Progresso na trilha');

    $this->actingAs($user)->post(route('notifications.gamification.read-all'))->assertNoContent();

    $this->actingAs($user)->getJson(route('notifications.gamification.unread'))
        ->assertOk()
        ->assertJsonPath('count', 0);
});
