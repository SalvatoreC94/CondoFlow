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

it('includes the condominium id and name on the condominos units in /api/me', function () {
    // Regression test: the frontend derives condominium_id from
    // units[].condominium_id/condominium.name when creating tickets and
    // loading the condomino's home/documents/announcements screens. If
    // UnitResource ever drops these fields, those screens silently break.
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);
    $resident = residentOf($unit);

    $response = $this->actingAs($resident, 'sanctum')->getJson('/api/me');

    $response->assertOk()
        ->assertJsonPath('data.units.0.condominium_id', $condominium->id)
        ->assertJsonPath('data.units.0.condominium.id', $condominium->id)
        ->assertJsonPath('data.units.0.condominium.name', $condominium->name);
});

it('logs out and invalidates the session', function () {
    $user = User::factory()->administrator()->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/logout')->assertOk();
});
