<?php

use App\Models\User;

it('returns the vapid public key', function () {
    $user = User::factory()->condomino()->create();

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/push/vapid-public-key');

    $response->assertOk()->assertJsonStructure(['public_key']);
});

it('rejects unauthenticated access to the vapid public key', function () {
    $this->getJson('/api/push/vapid-public-key')->assertUnauthorized();
});

it('lets an authenticated user subscribe to push notifications', function () {
    $user = User::factory()->condomino()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/push-subscriptions', [
        'endpoint' => 'https://push.example.com/subscription/abc123',
        'keys' => [
            'p256dh' => 'fake-p256dh-key',
            'auth' => 'fake-auth-token',
        ],
    ]);

    $response->assertOk();
    expect($user->pushSubscriptions()->where('endpoint', 'https://push.example.com/subscription/abc123')->exists())
        ->toBeTrue();
});

it('lets a user unsubscribe from push notifications', function () {
    $user = User::factory()->condomino()->create();
    $user->updatePushSubscription('https://push.example.com/subscription/abc123', 'key', 'token');

    $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/push-subscriptions', [
        'endpoint' => 'https://push.example.com/subscription/abc123',
    ]);

    $response->assertNoContent();
    expect($user->pushSubscriptions()->exists())->toBeFalse();
});
