<?php

use App\Models\User;
use App\Notifications\UserInvited;
use Illuminate\Support\Facades\Notification;

it('lets an administrator invite a condomino to their condominium', function () {
    Notification::fake();

    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);

    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/condominiums/{$condominium->id}/invitations", [
        'name' => 'Mario Rossi',
        'email' => 'mario.rossi@example.com',
        'role' => 'condomino',
        'unit_id' => $unit->id,
        'relationship' => 'owner',
    ]);

    $response->assertCreated();

    $invited = User::where('email', 'mario.rossi@example.com')->firstOrFail();
    expect($invited->status->value)->toBe('invited');
    expect($unit->users()->where('users.id', $invited->id)->exists())->toBeTrue();

    Notification::assertSentTo($invited, UserInvited::class);
});

it('prevents a caretaker from inviting users', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);
    $caretaker = caretakerFor($condominium);

    $this->actingAs($caretaker, 'sanctum')->postJson("/api/condominiums/{$condominium->id}/invitations", [
        'name' => 'Mario Rossi',
        'email' => 'mario.rossi@example.com',
        'role' => 'condomino',
        'unit_id' => $unit->id,
        'relationship' => 'owner',
    ])->assertForbidden();
});

it('prevents an administrator from inviting a user into a condominium they do not own', function () {
    $ownerAdmin = adminUser();
    $otherAdmin = adminUser();
    $condominium = condominiumFor($ownerAdmin);
    $unit = unitIn($condominium);

    $this->actingAs($otherAdmin, 'sanctum')->postJson("/api/condominiums/{$condominium->id}/invitations", [
        'name' => 'Mario Rossi',
        'email' => 'mario.rossi@example.com',
        'role' => 'condomino',
        'unit_id' => $unit->id,
        'relationship' => 'owner',
    ])->assertForbidden();
});

it('accepts an invitation and logs the user in', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);

    $invited = User::factory()->condomino()->invited()->create();
    $unit->users()->attach($invited->id, ['relationship' => 'owner', 'is_primary' => true]);

    $response = $this->postJson("/api/invitations/{$invited->invitation_token}/accept", [
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ]);

    $response->assertOk();
    $this->assertAuthenticatedAs($invited->fresh());

    $invited->refresh();
    expect($invited->status->value)->toBe('active');
    expect($invited->invitation_token)->toBeNull();
});

it('rejects an expired invitation token', function () {
    $invited = User::factory()->condomino()->invited()->create([
        'invitation_expires_at' => now()->subDay(),
    ]);

    $this->postJson("/api/invitations/{$invited->invitation_token}/accept", [
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])->assertStatus(422);
});

it('rejects an unknown invitation token', function () {
    $this->getJson('/api/invitations/does-not-exist')->assertStatus(422);
});
