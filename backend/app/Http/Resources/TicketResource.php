<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canSeeInternal = $user && $user->can('viewInternalComments', $this->resource);

        return [
            'id' => $this->id,
            'condominium_id' => $this->condominium_id,
            'unit' => new UnitResource($this->whenLoaded('unit')),
            'category' => new TicketCategoryResource($this->whenLoaded('category')),
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'assigned_caretaker' => new UserResource($this->whenLoaded('assignedCaretaker')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'allowed_next_statuses' => array_map(fn ($s) => $s->value, $this->status->allowedTransitions()),
            'location' => $this->location,
            'resolved_at' => $this->resolved_at,
            'closed_at' => $this->closed_at,
            'comments' => TicketCommentResource::collection(
                $this->whenLoaded('comments', fn () => $canSeeInternal
                    ? $this->comments
                    : $this->comments->where('is_internal', false)->values())
            ),
            'attachments' => TicketAttachmentResource::collection($this->whenLoaded('attachments')),
            'status_history' => TicketStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'interventions' => InterventionResource::collection($this->whenLoaded('interventions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
