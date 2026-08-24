<?php

use App\Models\User;

it('logs in with valid credentials', function () {
    $user = User::factory()->condomino()->create(['password' => bcrypt('correct-password')]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertOk()->assertJsonPath('data.email', $user->email);
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    $user = User::factory()->condomino()->create(['password' => bcrypt('correct-password')]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
    $this->assertGuest();
});

it('rejects login for a user who has not accepted their invitation yet', function () {
    $user = User::factory()->condomino()->invited()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(422);
});

it('returns the authenticated user on /api/me', function () {
    $user = User::factory()->administrator()->create();

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/me');

    $response->assertOk()->assertJsonPath('data.id', $user->id);
});

it('rejects unauthenticated access to /api/me', function () {
    $this->getJson('/api/me')->assertUnauthorized();
});

it('logs out and invalidates the session', function () {
    $user = User::factory()->administrator()->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/logout')->assertOk();
});
