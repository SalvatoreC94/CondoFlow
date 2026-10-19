<?php

use App\Models\Unit;

beforeEach(function () {
    $this->admin = adminUser();
    $this->condominium = condominiumFor($this->admin);
});

it('orders units by floor, floors ordered by when each one was first used', function () {
    // Deliberately not the Italian "T" convention, to prove the ordering
    // isn't tied to one language/convention — it only depends on creation
    // order, which an administrator naturally does ground floor first.
    Unit::factory()->create(['condominium_id' => $this->condominium->id, 'code' => 'U-GF', 'floor' => 'Ground Floor']);
    Unit::factory()->create(['condominium_id' => $this->condominium->id, 'code' => 'U1', 'floor' => '1st floor']);
    Unit::factory()->create(['condominium_id' => $this->condominium->id, 'code' => 'U2', 'floor' => '2nd floor']);
    // A second unit on the already-seen ground floor, created last: should
    // still land in the ground-floor group, not create a new one at the end.
    Unit::factory()->create(['condominium_id' => $this->condominium->id, 'code' => 'U-GF-2', 'floor' => 'Ground Floor']);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson("/api/condominiums/{$this->condominium->id}/units?per_page=50")
        ->assertOk();

    expect($response->json('data.*.code'))->toBe(['U-GF', 'U-GF-2', 'U1', 'U2']);
});

it('keeps a floors position when its first unit is later deleted', function () {
    $first = Unit::factory()->create(['condominium_id' => $this->condominium->id, 'code' => 'U-GF-1', 'floor' => 'Ground Floor']);
    Unit::factory()->create(['condominium_id' => $this->condominium->id, 'code' => 'U-GF-2', 'floor' => 'Ground Floor']);
    Unit::factory()->create(['condominium_id' => $this->condominium->id, 'code' => 'U1', 'floor' => '1st floor']);

    $first->delete();

    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson("/api/condominiums/{$this->condominium->id}/units?per_page=50")
        ->assertOk();

    expect($response->json('data.*.code'))->toBe(['U-GF-2', 'U1']);
});

it('lets an administrator create a unit with a free-text floor label', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/condominiums/{$this->condominium->id}/units", [
            'code' => 'A1',
            'floor' => 'Rez-de-chaussée',
            'type' => 'apartment',
        ])
        ->assertCreated()
        ->assertJsonPath('data.floor', 'Rez-de-chaussée');
});
