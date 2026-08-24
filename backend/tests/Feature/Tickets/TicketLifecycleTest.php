<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->admin = adminUser();
    $this->condominium = condominiumFor($this->admin);
    $this->unit = unitIn($this->condominium);
    $this->resident = residentOf($this->unit);
    $this->caretaker = caretakerFor($this->condominium);
    $this->category = TicketCategory::factory()->create();
});

it('lets a condomino create a ticket for their own unit', function () {
    $response = $this->actingAs($this->resident, 'sanctum')->postJson('/api/tickets', [
        'condominium_id' => $this->condominium->id,
        'unit_id' => $this->unit->id,
        'ticket_category_id' => $this->category->id,
        'title' => 'Perdita rubinetto cucina',
        'description' => 'Il rubinetto perde da ieri sera.',
        'priority' => 'high',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'new')
        ->assertJsonPath('data.reporter.id', $this->resident->id);

    $this->assertDatabaseHas('ticket_status_history', [
        'ticket_id' => $response->json('data.id'),
        'from_status' => null,
        'to_status' => 'new',
    ]);
});

it('walks a ticket through the valid status state machine', function () {
    $ticket = Ticket::factory()->create([
        'condominium_id' => $this->condominium->id,
        'unit_id' => $this->unit->id,
        'ticket_category_id' => $this->category->id,
        'created_by' => $this->resident->id,
    ]);

    $this->actingAs($this->caretaker, 'sanctum')
        ->patchJson("/api/tickets/{$ticket->id}/status", ['status' => TicketStatus::TakenInCharge->value])
        ->assertOk()
        ->assertJsonPath('data.status', 'taken_in_charge');

    $this->actingAs($this->caretaker, 'sanctum')
        ->patchJson("/api/tickets/{$ticket->id}/status", ['status' => TicketStatus::InProgress->value])
        ->assertOk();

    $response = $this->actingAs($this->caretaker, 'sanctum')
        ->patchJson("/api/tickets/{$ticket->id}/status", ['status' => TicketStatus::Resolved->value]);

    $response->assertOk()->assertJsonPath('data.status', 'resolved');
    expect($ticket->fresh()->resolved_at)->not->toBeNull();
});

it('rejects an invalid status transition', function () {
    $ticket = Ticket::factory()->create([
        'condominium_id' => $this->condominium->id,
        'unit_id' => $this->unit->id,
        'ticket_category_id' => $this->category->id,
        'created_by' => $this->resident->id,
        'status' => TicketStatus::New,
    ]);

    // New -> Resolved is not an allowed direct transition.
    $this->actingAs($this->caretaker, 'sanctum')
        ->patchJson("/api/tickets/{$ticket->id}/status", ['status' => TicketStatus::Resolved->value])
        ->assertUnprocessable();

    expect($ticket->fresh()->status)->toBe(TicketStatus::New);
});

it('prevents a condomino from changing a ticket status', function () {
    $ticket = Ticket::factory()->create([
        'condominium_id' => $this->condominium->id,
        'unit_id' => $this->unit->id,
        'ticket_category_id' => $this->category->id,
        'created_by' => $this->resident->id,
    ]);

    $this->actingAs($this->resident, 'sanctum')
        ->patchJson("/api/tickets/{$ticket->id}/status", ['status' => TicketStatus::TakenInCharge->value])
        ->assertForbidden();
});

it('hides internal comments from the reporting condomino but shows them to staff', function () {
    $ticket = Ticket::factory()->create([
        'condominium_id' => $this->condominium->id,
        'unit_id' => $this->unit->id,
        'ticket_category_id' => $this->category->id,
        'created_by' => $this->resident->id,
    ]);

    $this->actingAs($this->caretaker, 'sanctum')->postJson("/api/tickets/{$ticket->id}/comments", [
        'body' => 'Internal note about the plumber',
        'is_internal' => true,
    ])->assertCreated();

    $this->actingAs($this->caretaker, 'sanctum')->postJson("/api/tickets/{$ticket->id}/comments", [
        'body' => 'Public update for the resident',
        'is_internal' => false,
    ])->assertCreated();

    $residentView = $this->actingAs($this->resident, 'sanctum')->getJson("/api/tickets/{$ticket->id}");
    $residentComments = collect($residentView->json('data.comments'))->pluck('body');
    expect($residentComments)->toContain('Public update for the resident');
    expect($residentComments)->not->toContain('Internal note about the plumber');

    $staffView = $this->actingAs($this->caretaker, 'sanctum')->getJson("/api/tickets/{$ticket->id}");
    $staffComments = collect($staffView->json('data.comments'))->pluck('body');
    expect($staffComments)->toContain('Internal note about the plumber');
});

it('uploads an attachment and restricts its download to users who can view the ticket', function () {
    $ticket = Ticket::factory()->create([
        'condominium_id' => $this->condominium->id,
        'unit_id' => $this->unit->id,
        'ticket_category_id' => $this->category->id,
        'created_by' => $this->resident->id,
    ]);

    $file = UploadedFile::fake()->image('problema.jpg', 800, 600)->size(500);

    $upload = $this->actingAs($this->resident, 'sanctum')
        ->postJson("/api/tickets/{$ticket->id}/attachments", ['file' => $file]);

    $upload->assertCreated();
    $attachmentId = $upload->json('data.id');
    Storage::disk('local')->assertExists($ticket->attachments()->first()->path);

    // The reporter and staff can download it.
    $this->actingAs($this->resident, 'sanctum')
        ->get("/api/tickets/{$ticket->id}/attachments/{$attachmentId}/download")
        ->assertOk();

    // An unrelated resident from another condominium cannot.
    $otherAdmin = adminUser();
    $otherCondo = condominiumFor($otherAdmin);
    $otherUnit = unitIn($otherCondo);
    $otherResident = residentOf($otherUnit);

    $this->actingAs($otherResident, 'sanctum')
        ->get("/api/tickets/{$ticket->id}/attachments/{$attachmentId}/download")
        ->assertForbidden();
});

it('rejects an attachment upload with a disallowed mime type', function () {
    $ticket = Ticket::factory()->create([
        'condominium_id' => $this->condominium->id,
        'unit_id' => $this->unit->id,
        'ticket_category_id' => $this->category->id,
        'created_by' => $this->resident->id,
    ]);

    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

    $this->actingAs($this->resident, 'sanctum')
        ->postJson("/api/tickets/{$ticket->id}/attachments", ['file' => $file])
        ->assertUnprocessable();
});
