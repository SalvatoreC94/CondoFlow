<?php

use App\Enums\TicketStatus;
use App\Models\Supplier;
use App\Models\Ticket;
use App\Models\TicketCategory;

beforeEach(function () {
    $this->admin = adminUser();
    $this->condominium = condominiumFor($this->admin);
    $this->unit = unitIn($this->condominium);
    $this->resident = residentOf($this->unit);
    $this->caretaker = caretakerFor($this->condominium);
    $this->category = TicketCategory::factory()->create();
});

it('lets an administrator create a supplier and attach it to a condominium', function () {
    $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/suppliers', [
        'name' => 'Idraulica Rossi',
        'category' => 'Idraulica',
        'phone' => '0123456789',
        'condominium_ids' => [$this->condominium->id],
    ]);

    $response->assertCreated();
    $supplier = Supplier::findOrFail($response->json('data.id'));
    expect($supplier->condominiums()->where('condominiums.id', $this->condominium->id)->exists())->toBeTrue();
});

it('lets an administrator assign a supplier to a ticket in waiting_supplier status', function () {
    $supplier = Supplier::factory()->create(['administrator_id' => $this->admin->id]);
    $supplier->condominiums()->attach($this->condominium->id);

    $ticket = Ticket::factory()->create([
        'condominium_id' => $this->condominium->id,
        'unit_id' => $this->unit->id,
        'ticket_category_id' => $this->category->id,
        'created_by' => $this->resident->id,
        'status' => TicketStatus::TakenInCharge,
    ]);

    $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/tickets/{$ticket->id}", [
        'supplier_id' => $supplier->id,
    ]);

    $response->assertOk()->assertJsonPath('data.supplier.id', $supplier->id);
});

it('records the supplier and caretaker on an intervention', function () {
    $supplier = Supplier::factory()->create(['administrator_id' => $this->admin->id]);
    $supplier->condominiums()->attach($this->condominium->id);

    $ticket = Ticket::factory()->create([
        'condominium_id' => $this->condominium->id,
        'unit_id' => $this->unit->id,
        'ticket_category_id' => $this->category->id,
        'created_by' => $this->resident->id,
        'status' => TicketStatus::WaitingSupplier,
        'supplier_id' => $supplier->id,
    ]);

    $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/tickets/{$ticket->id}/interventions", [
        'supplier_id' => $supplier->id,
        'caretaker_id' => $this->caretaker->id,
        'notes' => 'Intervento programmato per domani mattina.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.supplier.id', $supplier->id)
        ->assertJsonPath('data.caretaker.id', $this->caretaker->id);
});

it('prevents a caretaker from creating suppliers', function () {
    $this->actingAs($this->caretaker, 'sanctum')->postJson('/api/suppliers', [
        'name' => 'Non consentito',
        'category' => 'Altro',
    ])->assertForbidden();
});
