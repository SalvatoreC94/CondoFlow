<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\AuditLog;
use App\Models\Condominium;
use App\Models\Ticket;
use App\Services\TicketWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    public function __construct(private readonly TicketWorkflow $workflow) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Ticket::query()->with(['unit', 'category', 'reporter', 'assignedCaretaker', 'supplier']);

        $query = match ($user->role) {
            UserRole::Administrator => $query->whereIn('condominium_id', $user->administeredCondominiums()->pluck('id')),
            UserRole::Caretaker => $query->whereIn('condominium_id', $user->assignedCondominiums()->pluck('condominiums.id')),
            UserRole::Condomino => $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereIn('unit_id', $user->units()->pluck('units.id'));
            }),
        };

        $query
            ->when($request->filled('condominium_id'), fn ($q) => $q->where('condominium_id', $request->integer('condominium_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->string('priority')))
            ->when($request->filled('ticket_category_id'), fn ($q) => $q->where('ticket_category_id', $request->integer('ticket_category_id')))
            ->when($request->filled('assigned_caretaker_id'), fn ($q) => $q->where('assigned_caretaker_id', $request->integer('assigned_caretaker_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'));

        $tickets = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 20));

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $condominium = Condominium::findOrFail($request->validated('condominium_id'));
        $this->authorize('view', $condominium);

        $ticket = $this->workflow->create($condominium, $request->user(), $request->safe()->except('condominium_id'));

        AuditLog::record('ticket.created', $ticket, [], $request->validated(), $condominium->id);

        return response()->json([
            'data' => new TicketResource($ticket->load(['unit', 'category', 'reporter'])),
        ], 201);
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load(['unit', 'category', 'reporter', 'assignedCaretaker', 'supplier', 'comments.user', 'attachments.uploader', 'statusHistory.changedBy', 'interventions.supplier', 'interventions.caretaker']);

        return response()->json(['data' => new TicketResource($ticket)]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $old = $ticket->only(array_keys($request->validated()));
        $ticket->update($request->validated());

        AuditLog::record('ticket.updated', $ticket, $old, $request->validated(), $ticket->condominium_id);

        return response()->json(['data' => new TicketResource($ticket->fresh(['unit', 'category', 'reporter', 'assignedCaretaker', 'supplier']))]);
    }

    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        AuditLog::record('ticket.deleted', $ticket, [], [], $ticket->condominium_id);

        return response()->json(null, 204);
    }
}
