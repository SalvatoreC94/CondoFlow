<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssemblyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'condominium_id' => $this->condominium_id,
            'title' => $this->title,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'agenda' => $this->agenda,
            'location' => $this->location,
            'scheduled_at' => $this->scheduled_at,
            'held_at' => $this->held_at,
            'minutes_document' => new DocumentResource($this->whenLoaded('minutesDocument')),
            'resolutions' => AssemblyResolutionResource::collection($this->whenLoaded('resolutions')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
        ];
    }
}
