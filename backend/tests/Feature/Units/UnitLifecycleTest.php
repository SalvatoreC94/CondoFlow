<?php

use App\Models\Unit;

beforeEach(function () {
    $this->admin = adminUser();
    $this->condominium = condominiumFor($this->admin);
    $this->unit = unitIn($this->condominium);
});

it('lets an administrator update a units fields', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->putJson("/api/units/{$this->unit->id}", [
            'code' => 'B202',
            'floor' => '2',
            'surface_sqm' => 85.5,
            'millesimi' => 120,
        ])
        ->assertOk()
        ->assertJsonPath('data.code', 'B202')
        ->assertJsonPath('data.floor', '2')
        ->assertJsonPath('data.millesimi', '120.000');
});

it('lets an administrator soft delete a unit', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->deleteJson("/api/units/{$this->unit->id}")
        ->assertNoContent();

    expect(Unit::find($this->unit->id))->toBeNull();
    expect(Unit::withTrashed()->find($this->unit->id))->not->toBeNull();

    $this->actingAs($this->admin, 'sanctum')
        ->getJson("/api/condominiums/{$this->condominium->id}/units")
        ->assertJsonCount(0, 'data');
});

it('lets an administrator attach an already registered resident to a second unit', function () {
    $resident = residentOf($this->unit);
    $secondUnit = unitIn($this->condominium);

    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/units/{$secondUnit->id}/residents", [
            'user_id' => $resident->id,
            'relationship' => 'tenant',
            'is_primary' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.residents.0.id', $resident->id)
        ->assertJsonPath('data.residents.0.relationship', 'tenant');

    expect($resident->fresh()->units()->count())->toBe(2);
});

it('lets an administrator detach a resident from a unit', function () {
    $resident = residentOf($this->unit);

    $this->actingAs($this->admin, 'sanctum')
        ->deleteJson("/api/units/{$this->unit->id}/residents/{$resident->id}")
        ->assertNoContent();

    expect($this->unit->fresh()->users()->count())->toBe(0);
});

it('prevents a caretaker from updating, deleting or managing residents on a unit', function () {
    $caretaker = caretakerFor($this->condominium);
    $resident = residentOf($this->unit);

    $this->actingAs($caretaker, 'sanctum')
        ->putJson("/api/units/{$this->unit->id}", ['code' => 'X1'])
        ->assertForbidden();

    $this->actingAs($caretaker, 'sanctum')
        ->deleteJson("/api/units/{$this->unit->id}")
        ->assertForbidden();

    $this->actingAs($caretaker, 'sanctum')
        ->deleteJson("/api/units/{$this->unit->id}/residents/{$resident->id}")
        ->assertForbidden();
});

it('never lets an administrator of one condominium manage a unit belonging to another', function () {
    $otherAdmin = adminUser();
    $otherCondominium = condominiumFor($otherAdmin);
    $otherUnit = unitIn($otherCondominium);

    $this->actingAs($this->admin, 'sanctum')
        ->putJson("/api/units/{$otherUnit->id}", ['code' => 'X1'])
        ->assertForbidden();

    $this->actingAs($this->admin, 'sanctum')
        ->deleteJson("/api/units/{$otherUnit->id}")
        ->assertForbidden();
});
