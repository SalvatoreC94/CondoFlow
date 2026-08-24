<?php

use App\Models\Building;
use App\Models\Unit;

beforeEach(function () {
    $this->admin = adminUser();
    $this->condominium = condominiumFor($this->admin);
    $this->buildingA = Building::factory()->create(['condominium_id' => $this->condominium->id]);
    $this->buildingB = Building::factory()->create(['condominium_id' => $this->condominium->id]);

    $this->unitInA = Unit::factory()->create([
        'condominium_id' => $this->condominium->id,
        'building_id' => $this->buildingA->id,
    ]);
    $this->unitInB = Unit::factory()->create([
        'condominium_id' => $this->condominium->id,
        'building_id' => $this->buildingB->id,
    ]);

    $this->residentInA = residentOf($this->unitInA);
    $this->residentInB = residentOf($this->unitInB);
});

it('shows a "everyone" announcement to every resident of the condominium', function () {
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/announcements', [
        'condominium_id' => $this->condominium->id,
        'title' => 'Assemblea condominiale',
        'content' => 'Vi aspettiamo tutti.',
        'priority' => 'normal',
        'audience' => 'all',
    ])->assertCreated();

    $listA = $this->actingAs($this->residentInA, 'sanctum')
        ->getJson("/api/announcements?condominium_id={$this->condominium->id}");
    $listB = $this->actingAs($this->residentInB, 'sanctum')
        ->getJson("/api/announcements?condominium_id={$this->condominium->id}");

    expect(collect($listA->json('data'))->pluck('title'))->toContain('Assemblea condominiale');
    expect(collect($listB->json('data'))->pluck('title'))->toContain('Assemblea condominiale');
});

it('only shows a building-targeted announcement to residents of that building', function () {
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/announcements', [
        'condominium_id' => $this->condominium->id,
        'title' => 'Lavori Scala A',
        'content' => 'Interruzione idrica scala A.',
        'priority' => 'important',
        'audience' => 'buildings',
        'building_ids' => [$this->buildingA->id],
    ])->assertCreated();

    $listA = $this->actingAs($this->residentInA, 'sanctum')
        ->getJson("/api/announcements?condominium_id={$this->condominium->id}");
    $listB = $this->actingAs($this->residentInB, 'sanctum')
        ->getJson("/api/announcements?condominium_id={$this->condominium->id}");

    expect(collect($listA->json('data'))->pluck('title'))->toContain('Lavori Scala A');
    expect(collect($listB->json('data'))->pluck('title'))->not->toContain('Lavori Scala A');
});

it('only shows a specific-users announcement to the targeted users', function () {
    $create = $this->actingAs($this->admin, 'sanctum')->postJson('/api/announcements', [
        'condominium_id' => $this->condominium->id,
        'title' => 'Messaggio riservato',
        'content' => 'Solo per te.',
        'priority' => 'normal',
        'audience' => 'users',
        'user_ids' => [$this->residentInA->id],
    ]);
    $create->assertCreated();
    $announcementId = $create->json('data.id');

    $this->actingAs($this->residentInA, 'sanctum')
        ->getJson("/api/announcements/{$announcementId}")
        ->assertOk();

    $this->actingAs($this->residentInB, 'sanctum')
        ->getJson("/api/announcements/{$announcementId}")
        ->assertForbidden();
});

it('prevents a condomino from creating an announcement', function () {
    $this->actingAs($this->residentInA, 'sanctum')->postJson('/api/announcements', [
        'condominium_id' => $this->condominium->id,
        'title' => 'Non dovrei poterlo fare',
        'content' => 'Contenuto',
        'priority' => 'normal',
        'audience' => 'all',
    ])->assertForbidden();
});
