<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Condominium;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Notifications\TicketCommented;
use App\Notifications\TicketCreated;
use App\Notifications\TicketStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Centralizes the ticket status state machine and the notification fan-out
 * that has to stay consistent across the create/status-update/comment
 * endpoints — the one piece of ticket logic worth sharing.
 */
class TicketWorkflow
{
    public function create(Condominium $condominium, User $reporter, array $data): Ticket
    {
        return DB::transaction(function () use ($condominium, $reporter, $data) {
            $ticket = $condominium->tickets()->create([
                ...$data,
                'created_by' => $reporter->id,
                'status' => TicketStatus::New,
            ]);

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'from_status' => null,
                'to_status' => TicketStatus::New->value,
                'changed_by' => $reporter->id,
            ]);

            $staff = $condominium->administrator()->get()->merge($condominium->caretakers()->get());
            Notification::send($staff, new TicketCreated($ticket));

            return $ticket;
        });
    }

    public function transitionTo(Ticket $ticket, TicketStatus $target, User $changedBy, ?string $note = null): Ticket
    {
        if (! $ticket->status->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => ["Non è possibile passare da \"{$ticket->status->label()}\" a \"{$target->label()}\"."],
            ]);
        }

        return DB::transaction(function () use ($ticket, $target, $changedBy, $note) {
            $from = $ticket->status;

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'from_status' => $from->value,
                'to_status' => $target->value,
                'changed_by' => $changedBy->id,
                'note' => $note,
            ]);

            $ticket->status = $target;
            if (in_array($target, [TicketStatus::Resolved, TicketStatus::Closed], true) && ! $ticket->resolved_at) {
                $ticket->resolved_at = now();
            }
            if ($target === TicketStatus::Closed) {
                $ticket->closed_at = now();
            }
            $ticket->save();

            $ticket->reporter?->notify(new TicketStatusChanged($ticket));

            return $ticket;
        });
    }

    public function addComment(Ticket $ticket, User $author, string $body, bool $isInternal): TicketComment
    {
        $comment = $ticket->comments()->create([
            'user_id' => $author->id,
            'body' => $body,
            'is_internal' => $isInternal,
        ]);

        $recipients = collect([$ticket->reporter, $ticket->assignedCaretaker])
            ->filter()
            ->reject(fn (User $u) => $u->id === $author->id)
            ->when($isInternal, fn ($collection) => $collection->reject(fn (User $u) => $u->id === $ticket->created_by))
            ->unique('id');

        Notification::send($recipients, new TicketCommented($comment));

        return $comment;
    }
}
