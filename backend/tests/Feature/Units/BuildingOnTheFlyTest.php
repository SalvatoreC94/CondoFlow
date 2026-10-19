<?php

use App\Models\Building;
use App\Models\Unit;

beforeEach(function () {
    $this->admin = adminUser();
    $this->condominium = condominiumFor($this->admin);
});

it('creates a building on the fly when a unit names one that does not exist yet', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/condominiums/{$this->condominium->id}/units", [
            'code' => 'A1',
            'floor' => 'T',
            'building_name' => 'Edificio A',
            'type' => 'apartment',
        ])
        ->assertCreated()
        ->assertJsonPath('data.building.name', 'Edificio A');

    expect(Building::where('condominium_id', $this->condominium->id)->where('name', 'Edificio A')->exists())->toBeTrue();
});

it('reuses an existing building instead of creating a duplicate', function () {
    $building = Building::factory()->create(['condominium_id' => $this->condominium->id, 'name' => 'Edificio A']);

    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/condominiums/{$this->condominium->id}/units", [
            'code' => 'A2',
            'building_name' => 'Edificio A',
            'type' => 'apartment',
        ])
        ->assertCreated()
        ->assertJsonPath('data.building.id', $building->id);

    expect(Building::where('condominium_id', $this->condominium->id)->where('name', 'Edificio A')->count())->toBe(1);
});

it('creates a unit with no building when building_name is left blank', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/condominiums/{$this->condominium->id}/units", [
            'code' => 'A3',
            'type' => 'apartment',
        ])
        ->assertCreated()
        ->assertJsonPath('data.building', null);
});

it('lets an administrator change a units building by name when updating it', function () {
    $unit = Unit::factory()->create(['condominium_id' => $this->condominium->id]);

    $this->actingAs($this->admin, 'sanctum')
        ->putJson("/api/units/{$unit->id}", ['building_name' => 'Scala 2'])
        ->assertOk()
        ->assertJsonPath('data.building.name', 'Scala 2');

    expect($unit->fresh()->building->name)->toBe('Scala 2');
});

it('never lets an administrator of one condominium create a building under another condominiums unit', function () {
    $otherAdmin = adminUser();
    $otherCondominium = condominiumFor($otherAdmin);

    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/condominiums/{$otherCondominium->id}/units", [
            'code' => 'X1',
            'building_name' => 'Edificio Intruso',
            'type' => 'apartment',
        ])
        ->assertForbidden();

    expect(Building::where('name', 'Edificio Intruso')->exists())->toBeFalse();
});
