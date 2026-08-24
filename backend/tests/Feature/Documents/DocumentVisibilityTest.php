<?php

use App\Models\DocumentCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->admin = adminUser();
    $this->condominium = condominiumFor($this->admin);
    $this->unit = unitIn($this->condominium);
    $this->resident = residentOf($this->unit);
    $this->category = DocumentCategory::factory()->create();
});

it('lets an administrator upload a document visible to everyone', function () {
    $file = UploadedFile::fake()->create('regolamento.pdf', 200, 'application/pdf');

    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/documents', [
        'condominium_id' => $this->condominium->id,
        'document_category_id' => $this->category->id,
        'title' => 'Regolamento condominiale',
        'visibility' => 'all',
        'file' => $file,
    ]);

    $response->assertCreated();

    $this->actingAs($this->resident, 'sanctum')
        ->getJson("/api/documents/{$response->json('data.id')}")
        ->assertOk();
});

it('hides an administrators-only document from a condomino', function () {
    $file = UploadedFile::fake()->create('bilancio-interno.pdf', 200, 'application/pdf');

    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/documents', [
        'condominium_id' => $this->condominium->id,
        'document_category_id' => $this->category->id,
        'title' => 'Bilancio interno',
        'visibility' => 'administrators',
        'file' => $file,
    ]);

    $documentId = $response->json('data.id');

    $this->actingAs($this->resident, 'sanctum')
        ->getJson("/api/documents/{$documentId}")
        ->assertForbidden();

    // It should also be absent from the resident's document listing.
    $list = $this->actingAs($this->resident, 'sanctum')
        ->getJson("/api/documents?condominium_id={$this->condominium->id}");
    expect(collect($list->json('data'))->pluck('id'))->not->toContain($documentId);
});

it('lets the uploader download the file it stored', function () {
    $file = UploadedFile::fake()->create('verbale.pdf', 200, 'application/pdf');

    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/documents', [
        'condominium_id' => $this->condominium->id,
        'document_category_id' => $this->category->id,
        'title' => 'Verbale assemblea',
        'visibility' => 'all',
        'file' => $file,
    ]);

    $this->actingAs($this->admin, 'sanctum')
        ->get("/api/documents/{$response->json('data.id')}/download")
        ->assertOk();
});

it('prevents a condomino from uploading a document', function () {
    $file = UploadedFile::fake()->create('qualcosa.pdf', 100, 'application/pdf');

    $this->actingAs($this->resident, 'sanctum')->postJson('/api/documents', [
        'condominium_id' => $this->condominium->id,
        'document_category_id' => $this->category->id,
        'title' => 'Tentativo',
        'visibility' => 'all',
        'file' => $file,
    ])->assertForbidden();
});
