<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\UpdateTicketStatusRequest;
use App\Http\Resources\TicketResource;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Services\TicketWorkflow;
use Illuminate\Http\JsonResponse;

class TicketStatusController extends Controller
{
    public function __construct(private readonly TicketWorkflow $workflow) {}

    public function update(UpdateTicketStatusRequest $request, Ticket $ticket): JsonResponse
    {
        $from = $ticket->status;
        $target = TicketStatus::from($request->validated('status'));

        $this->workflow->transitionTo($ticket, $target, $request->user(), $request->validated('note'));

        AuditLog::record(
            'ticket.status_changed',
            $ticket,
            ['status' => $from->value],
            ['status' => $target->value],
            $ticket->condominium_id
        );

        return response()->json([
            'data' => new TicketResource($ticket->fresh(['unit', 'category', 'reporter', 'assignedCaretaker', 'supplier', 'statusHistory.changedBy'])),
        ]);
    }
}
