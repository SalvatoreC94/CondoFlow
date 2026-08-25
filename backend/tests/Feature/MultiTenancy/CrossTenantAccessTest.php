<?php

use App\Enums\TicketStatus;
use App\Models\Announcement;
use App\Models\Assembly;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Expense;
use App\Models\Installment;
use App\Models\InstallmentCharge;
use App\Models\Supplier;
use App\Models\Ticket;
use App\Models\TicketCategory;

beforeEach(function () {
    $this->adminA = adminUser();
    $this->condoA = condominiumFor($this->adminA);
    $this->unitA = unitIn($this->condoA);
    $this->residentA = residentOf($this->unitA);
    $this->caretakerA = caretakerFor($this->condoA);

    $this->adminB = adminUser();
    $this->condoB = condominiumFor($this->adminB);
    $this->unitB = unitIn($this->condoB);
    $this->residentB = residentOf($this->unitB);

    $this->category = TicketCategory::factory()->create();
    $this->ticketB = Ticket::factory()->create([
        'condominium_id' => $this->condoB->id,
        'unit_id' => $this->unitB->id,
        'ticket_category_id' => $this->category->id,
        'created_by' => $this->residentB->id,
    ]);
});

it('never lets a resident of condominium A view condominium B', function () {
    $this->actingAs($this->residentA, 'sanctum')
        ->getJson("/api/condominiums/{$this->condoB->id}")
        ->assertForbidden();
});

it('never lets an administrator of condominium A manage condominium B', function () {
    $this->actingAs($this->adminA, 'sanctum')
        ->putJson("/api/condominiums/{$this->condoB->id}", ['name' => 'Hijacked'])
        ->assertForbidden();

    $this->actingAs($this->adminA, 'sanctum')
        ->deleteJson("/api/condominiums/{$this->condoB->id}")
        ->assertForbidden();

    expect($this->condoB->fresh()->name)->not->toBe('Hijacked');
});

it('excludes other tenants condominiums from the index listing', function () {
    $response = $this->actingAs($this->adminA, 'sanctum')->getJson('/api/condominiums');

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($this->condoA->id);
    expect($ids)->not->toContain($this->condoB->id);
});

it('never lets a resident view another condominiums unit even by guessing its id', function () {
    $this->actingAs($this->residentA, 'sanctum')
        ->getJson("/api/units/{$this->unitB->id}")
        ->assertForbidden();
});

it('never lets a resident view another condominiums ticket by guessing its id', function () {
    $this->actingAs($this->residentA, 'sanctum')
        ->getJson("/api/tickets/{$this->ticketB->id}")
        ->assertForbidden();
});

it('never lets an administrator of another condominium manage a ticket by guessing its id', function () {
    $this->actingAs($this->adminA, 'sanctum')
        ->patchJson("/api/tickets/{$this->ticketB->id}/status", ['status' => TicketStatus::TakenInCharge->value])
        ->assertForbidden();

    expect($this->ticketB->fresh()->status)->toBe(TicketStatus::New);
});

it('never returns another tenants tickets in the ticket index, even without filters', function () {
    Ticket::factory()->create([
        'condominium_id' => $this->condoA->id,
        'unit_id' => $this->unitA->id,
        'ticket_category_id' => $this->category->id,
        'created_by' => $this->residentA->id,
    ]);

    $response = $this->actingAs($this->residentA, 'sanctum')->getJson('/api/tickets');

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->not->toContain($this->ticketB->id);
});

it('rejects a ticket creation request whose condominium_id belongs to a tenant the user cannot access', function () {
    $response = $this->actingAs($this->residentA, 'sanctum')->postJson('/api/tickets', [
        'condominium_id' => $this->condoB->id,
        'unit_id' => $this->unitB->id,
        'ticket_category_id' => $this->category->id,
        'title' => 'Attempted cross-tenant ticket',
        'description' => 'This should never be created.',
        'priority' => 'low',
    ]);

    $response->assertForbidden();
    expect(Ticket::where('title', 'Attempted cross-tenant ticket')->exists())->toBeFalse();
});

it('never lets a resident of condominium A read documents of condominium B', function () {
    $category = DocumentCategory::factory()->create();
    $document = Document::factory()->create([
        'condominium_id' => $this->condoB->id,
        'document_category_id' => $category->id,
        'uploaded_by' => $this->adminB->id,
    ]);

    $this->actingAs($this->residentA, 'sanctum')
        ->getJson("/api/documents/{$document->id}")
        ->assertForbidden();

    $this->actingAs($this->residentA, 'sanctum')
        ->getJson("/api/documents?condominium_id={$this->condoB->id}")
        ->assertForbidden();
});

it('never lets a resident of condominium A read announcements of condominium B', function () {
    $announcement = Announcement::factory()->create([
        'condominium_id' => $this->condoB->id,
        'created_by' => $this->adminB->id,
    ]);

    $this->actingAs($this->residentA, 'sanctum')
        ->getJson("/api/announcements/{$announcement->id}")
        ->assertForbidden();
});

it('never lets a caretaker act on a condominium they are not assigned to', function () {
    $this->actingAs($this->caretakerA, 'sanctum')
        ->getJson("/api/condominiums/{$this->condoB->id}/units")
        ->assertForbidden();

    $this->actingAs($this->caretakerA, 'sanctum')
        ->patchJson("/api/tickets/{$this->ticketB->id}/status", ['status' => TicketStatus::TakenInCharge->value])
        ->assertForbidden();
});

it('never lets a supplier owned by another administrator be read or updated', function () {
    $supplier = Supplier::factory()->create(['administrator_id' => $this->adminB->id]);

    $this->actingAs($this->adminA, 'sanctum')
        ->getJson("/api/suppliers/{$supplier->id}")
        ->assertForbidden();

    $this->actingAs($this->adminA, 'sanctum')
        ->putJson("/api/suppliers/{$supplier->id}", ['name' => 'Hijacked Supplier'])
        ->assertForbidden();
});

it('never lets an administrator of condominium A view or manage condominium Bs expenses and installments', function () {
    $expenseB = Expense::factory()->create(['condominium_id' => $this->condoB->id, 'created_by' => $this->adminB->id]);
    $installmentB = Installment::factory()->create(['condominium_id' => $this->condoB->id, 'created_by' => $this->adminB->id]);

    $this->actingAs($this->adminA, 'sanctum')
        ->getJson("/api/condominiums/{$this->condoB->id}/expenses")
        ->assertForbidden();

    $this->actingAs($this->adminA, 'sanctum')
        ->putJson("/api/expenses/{$expenseB->id}", ['description' => 'Hijacked'])
        ->assertForbidden();

    $this->actingAs($this->adminA, 'sanctum')
        ->getJson("/api/installments/{$installmentB->id}")
        ->assertForbidden();

    $this->actingAs($this->adminA, 'sanctum')
        ->deleteJson("/api/installments/{$installmentB->id}")
        ->assertForbidden();

    expect($expenseB->fresh()->description)->not->toBe('Hijacked');
});

it('never lets an administrator of condominium A mark a condominium Bs charge as paid', function () {
    $installmentB = Installment::factory()->create(['condominium_id' => $this->condoB->id, 'created_by' => $this->adminB->id]);
    $chargeB = InstallmentCharge::factory()->create(['installment_id' => $installmentB->id, 'unit_id' => $this->unitB->id]);

    $this->actingAs($this->adminA, 'sanctum')
        ->patchJson("/api/installment-charges/{$chargeB->id}", ['paid' => true])
        ->assertForbidden();

    expect($chargeB->fresh()->paid)->toBeFalse();
});

it('never lets a resident of condominium A view or manage condominium Bs assemblies', function () {
    $assemblyB = Assembly::factory()->create(['condominium_id' => $this->condoB->id, 'created_by' => $this->adminB->id]);

    $this->actingAs($this->residentA, 'sanctum')
        ->getJson("/api/assemblies/{$assemblyB->id}")
        ->assertForbidden();

    $this->actingAs($this->adminA, 'sanctum')
        ->putJson("/api/assemblies/{$assemblyB->id}", ['status' => 'held'])
        ->assertForbidden();

    $this->actingAs($this->adminA, 'sanctum')
        ->postJson("/api/assemblies/{$assemblyB->id}/resolutions", ['description' => 'Hijacked', 'outcome' => 'approved'])
        ->assertForbidden();

    expect($assemblyB->fresh()->status->value)->toBe('scheduled');
});
