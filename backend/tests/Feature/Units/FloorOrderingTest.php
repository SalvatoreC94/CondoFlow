<?php

use App\Models\Unit;

beforeEach(function () {
    $this->admin = adminUser();
    $this->condominium = condominiumFor($this->admin);
});

it('orders units by floor_order rather than any hardcoded floor label', function () {
    // Deliberately not the Italian "T" convention, to prove the ordering
    // isn't tied to one language/convention.
    Unit::factory()->create(['condominium_id' => $this->condominium->id, 'code' => 'U2', 'floor' => '2nd floor', 'floor_order' => 2]);
    Unit::factory()->create(['condominium_id' => $this->condominium->id, 'code' => 'U-GF', 'floor' => 'Ground Floor', 'floor_order' => 0]);
    Unit::factory()->create(['condominium_id' => $this->condominium->id, 'code' => 'U-B1', 'floor' => 'Basement', 'floor_order' => -1]);
    Unit::factory()->create(['condominium_id' => $this->condominium->id, 'code' => 'U1', 'floor' => '1st floor', 'floor_order' => 1]);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson("/api/condominiums/{$this->condominium->id}/units?per_page=50")
        ->assertOk();

    expect($response->json('data.*.code'))->toBe(['U-B1', 'U-GF', 'U1', 'U2']);
});

it('sorts units with no floor_order after every ordered unit', function () {
    Unit::factory()->create(['condominium_id' => $this->condominium->id, 'code' => 'ORDERED', 'floor_order' => 5]);
    Unit::factory()->create(['condominium_id' => $this->condominium->id, 'code' => 'UNORDERED', 'floor_order' => null]);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson("/api/condominiums/{$this->condominium->id}/units?per_page=50")
        ->assertOk();

    expect($response->json('data.*.code'))->toBe(['ORDERED', 'UNORDERED']);
});

it('lets an administrator set floor_order when creating a unit', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/condominiums/{$this->condominium->id}/units", [
            'code' => 'A1',
            'floor' => 'Rez-de-chaussée',
            'floor_order' => 0,
            'type' => 'apartment',
        ])
        ->assertCreated()
        ->assertJsonPath('data.floor_order', 0);
});
