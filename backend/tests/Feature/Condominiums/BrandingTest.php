<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->admin = adminUser();
    $this->condominium = condominiumFor($this->admin);
    $this->unit = unitIn($this->condominium);
    $this->resident = residentOf($this->unit);
    $this->caretaker = caretakerFor($this->condominium);
});

it('lets the administrator set the brand color', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->putJson("/api/condominiums/{$this->condominium->id}", ['brand_color' => '#3B82F6'])
        ->assertOk()
        ->assertJsonPath('data.brand_color', '#3B82F6');

    expect($this->condominium->fresh()->brand_color)->toBe('#3B82F6');
});

it('rejects an invalid brand color', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->putJson("/api/condominiums/{$this->condominium->id}", ['brand_color' => 'blue'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('brand_color');
});

it('prevents a caretaker or a resident from changing the brand color', function () {
    $this->actingAs($this->caretaker, 'sanctum')
        ->putJson("/api/condominiums/{$this->condominium->id}", ['brand_color' => '#3B82F6'])
        ->assertForbidden();

    $this->actingAs($this->resident, 'sanctum')
        ->putJson("/api/condominiums/{$this->condominium->id}", ['brand_color' => '#3B82F6'])
        ->assertForbidden();

    expect($this->condominium->fresh()->brand_color)->toBeNull();
});

it('lets the administrator upload, view, and remove a logo', function () {
    $file = UploadedFile::fake()->image('logo.png', 200, 200);

    $upload = $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/condominiums/{$this->condominium->id}/logo", ['logo' => $file])
        ->assertOk()
        ->assertJsonPath('data.has_logo', true);

    $logoUrl = $upload->json('data.logo_url');
    expect($logoUrl)->not->toBeNull();

    Storage::disk('local')->assertExists($this->condominium->fresh()->logo_path);

    $this->actingAs($this->admin, 'sanctum')
        ->get($logoUrl)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    $this->actingAs($this->admin, 'sanctum')
        ->deleteJson("/api/condominiums/{$this->condominium->id}/logo")
        ->assertOk()
        ->assertJsonPath('data.has_logo', false);

    expect($this->condominium->fresh()->logo_path)->toBeNull();
});

it('replaces the previous logo file when a new one is uploaded', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/condominiums/{$this->condominium->id}/logo", ['logo' => UploadedFile::fake()->image('first.png')])
        ->assertOk();

    $firstPath = $this->condominium->fresh()->logo_path;
    Storage::disk('local')->assertExists($firstPath);

    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/condominiums/{$this->condominium->id}/logo", ['logo' => UploadedFile::fake()->image('second.png')])
        ->assertOk();

    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($this->condominium->fresh()->logo_path);
});

it('rejects a logo upload from a caretaker or a resident', function () {
    $file = UploadedFile::fake()->image('logo.png');

    $this->actingAs($this->caretaker, 'sanctum')
        ->postJson("/api/condominiums/{$this->condominium->id}/logo", ['logo' => $file])
        ->assertForbidden();

    $this->actingAs($this->resident, 'sanctum')
        ->postJson("/api/condominiums/{$this->condominium->id}/logo", ['logo' => $file])
        ->assertForbidden();
});

it('rejects a non-image file as a logo', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/condominiums/{$this->condominium->id}/logo", [
            'logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('logo');
});

it('lets a resident of the condominium see the logo, but not residents of another condominium', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/condominiums/{$this->condominium->id}/logo", ['logo' => UploadedFile::fake()->image('logo.png')])
        ->assertOk();

    $this->actingAs($this->resident, 'sanctum')
        ->get(route('condominiums.logo', ['condominium' => $this->condominium->id]))
        ->assertOk();

    $otherAdmin = adminUser();
    $otherCondominium = condominiumFor($otherAdmin);
    $otherUnit = unitIn($otherCondominium);
    $outsider = residentOf($otherUnit);

    $this->actingAs($outsider, 'sanctum')
        ->get(route('condominiums.logo', ['condominium' => $this->condominium->id]))
        ->assertForbidden();
});

it('returns 404 when a condominium has no logo', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->get(route('condominiums.logo', ['condominium' => $this->condominium->id]))
        ->assertNotFound();
});
