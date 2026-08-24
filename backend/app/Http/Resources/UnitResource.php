<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'condominium_id' => $this->condominium_id,
            'condominium' => new CondominiumResource($this->whenLoaded('condominium')),
            'building' => new BuildingResource($this->whenLoaded('building')),
            'code' => $this->code,
            'floor' => $this->floor,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'surface_sqm' => $this->surface_sqm,
            'notes' => $this->notes,
            'residents' => UserResource::collection($this->whenLoaded('users')),
        ];
    }
}
