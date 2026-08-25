<?php

use App\Models\Assembly;
use App\Notifications\AssemblyScheduled;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;

it('lets an administrator convene an assembly and notifies every resident', function () {
    Notification::fake();

    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);
    $resident = residentOf($unit);

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/assemblies', [
        'condominium_id' => $condominium->id,
        'title' => 'Assemblea ordinaria 2026',
        'type' => 'ordinary',
        'agenda' => "1. Bilancio consuntivo\n2. Bilancio preventivo",
        'location' => 'Sede amministrazione',
        'scheduled_at' => now()->addMonth()->toDateTimeString(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'scheduled')
        ->assertJsonPath('data.type_label', 'Ordinaria');

    Notification::assertSentTo($resident, AssemblyScheduled::class);
});

it('prevents a condomino from creating an assembly', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);
    $resident = residentOf($unit);

    $this->actingAs($resident, 'sanctum')->postJson('/api/assemblies', [
        'condominium_id' => $condominium->id,
        'title' => 'Non dovrei poterla convocare',
        'type' => 'ordinary',
        'agenda' => 'Ordine del giorno',
        'scheduled_at' => now()->addMonth()->toDateTimeString(),
    ])->assertForbidden();
});

it('prevents a caretaker from creating an assembly', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $caretaker = caretakerFor($condominium);

    $this->actingAs($caretaker, 'sanctum')->postJson('/api/assemblies', [
        'condominium_id' => $condominium->id,
        'title' => 'Non dovrei poterla convocare',
        'type' => 'ordinary',
        'agenda' => 'Ordine del giorno',
        'scheduled_at' => now()->addMonth()->toDateTimeString(),
    ])->assertForbidden();
});

it('lets staff and residents of the condominium view an assembly', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);
    $resident = residentOf($unit);
    $caretaker = caretakerFor($condominium);
    $assembly = Assembly::factory()->create(['condominium_id' => $condominium->id, 'created_by' => $admin->id]);

    $this->actingAs($resident, 'sanctum')->getJson("/api/assemblies/{$assembly->id}")->assertOk();
    $this->actingAs($caretaker, 'sanctum')->getJson("/api/assemblies/{$assembly->id}")->assertOk();
    $this->actingAs($admin, 'sanctum')->getJson("/api/assemblies/{$assembly->id}")->assertOk();
});

it('lets an administrator record resolutions and mark the assembly as held', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $assembly = Assembly::factory()->create(['condominium_id' => $condominium->id, 'created_by' => $admin->id]);

    $this->actingAs($admin, 'sanctum')->postJson("/api/assemblies/{$assembly->id}/resolutions", [
        'description' => 'Approvazione bilancio consuntivo',
        'outcome' => 'approved',
    ])->assertCreated();

    $update = $this->actingAs($admin, 'sanctum')->putJson("/api/assemblies/{$assembly->id}", [
        'status' => 'held',
        'held_at' => now()->toDateTimeString(),
    ]);

    $update->assertOk()
        ->assertJsonPath('data.status', 'held')
        ->assertJsonCount(1, 'data.resolutions');
});

it('prevents a condomino from adding a resolution or marking the assembly as held', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);
    $resident = residentOf($unit);
    $assembly = Assembly::factory()->create(['condominium_id' => $condominium->id, 'created_by' => $admin->id]);

    $this->actingAs($resident, 'sanctum')->postJson("/api/assemblies/{$assembly->id}/resolutions", [
        'description' => 'Non dovrei poterlo fare',
        'outcome' => 'approved',
    ])->assertForbidden();

    $this->actingAs($resident, 'sanctum')->putJson("/api/assemblies/{$assembly->id}", [
        'status' => 'held',
    ])->assertForbidden();
});

it('lets an administrator upload the minutes and links them to the assembly', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $assembly = Assembly::factory()->create(['condominium_id' => $condominium->id, 'created_by' => $admin->id]);

    $file = UploadedFile::fake()->create('verbale.pdf', 200, 'application/pdf');

    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/assemblies/{$assembly->id}/minutes", [
        'file' => $file,
    ]);

    $response->assertOk();
    expect($response->json('data.minutes_document.original_name'))->toBe('verbale.pdf');
    expect($assembly->fresh()->minutes_document_id)->not->toBeNull();
});

it('lets an administrator delete a resolution', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $assembly = Assembly::factory()->create(['condominium_id' => $condominium->id, 'created_by' => $admin->id]);
    $resolution = $assembly->resolutions()->create([
        'description' => 'Delibera da rimuovere',
        'outcome' => 'approved',
    ]);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/assembly-resolutions/{$resolution->id}")
        ->assertNoContent();

    expect($assembly->resolutions()->count())->toBe(0);
});
