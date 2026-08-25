<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

it('logs in with a phone number instead of an email', function () {
    $user = User::factory()->condomino()->create([
        'email' => null,
        'phone' => '+39 333 1112223',
        'password' => bcrypt('correct-password'),
    ]);

    $response = $this->postJson('/api/login', [
        'identifier' => '+39 333 1112223',
        'password' => 'correct-password',
    ]);

    $response->assertOk()->assertJsonPath('data.phone', $user->phone);
    $this->assertAuthenticatedAs($user);
});

it('invites a condomino with only a phone number and returns a shareable link instead of emailing it', function () {
    Notification::fake();

    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);

    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/condominiums/{$condominium->id}/invitations", [
        'name' => 'Mario Rossi',
        'phone' => '+39 333 9998887',
        'role' => 'condomino',
        'unit_id' => $unit->id,
        'relationship' => 'owner',
    ]);

    $response->assertCreated();
    $response->assertJsonStructure(['data', 'invitation_url']);

    $invited = User::where('phone', '+39 333 9998887')->firstOrFail();
    expect($invited->email)->toBeNull();
    expect($response->json('invitation_url'))->toContain($invited->invitation_token);

    Notification::assertNothingSent();
});

it('rejects an invitation with neither an email nor a phone number', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);

    $this->actingAs($admin, 'sanctum')->postJson("/api/condominiums/{$condominium->id}/invitations", [
        'name' => 'Mario Rossi',
        'role' => 'condomino',
        'unit_id' => $unit->id,
        'relationship' => 'owner',
    ])->assertStatus(422);
});

it('accepts a phone-only invitation and logs the user in', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);

    $invited = User::factory()->condomino()->invited()->create([
        'email' => null,
        'phone' => '+39 333 4445556',
    ]);
    $unit->users()->attach($invited->id, ['relationship' => 'owner', 'is_primary' => true]);

    $response = $this->postJson("/api/invitations/{$invited->invitation_token}/accept", [
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ]);

    $response->assertOk();
    $this->assertAuthenticatedAs($invited->fresh());
});
