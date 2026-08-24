<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CondominiumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'total_units' => $this->total_units,
            'units_count' => $this->whenCounted('units'),
            'description' => $this->description,
            'administrator' => new UserResource($this->whenLoaded('administrator')),
            'created_at' => $this->created_at,
        ];
    }
}
