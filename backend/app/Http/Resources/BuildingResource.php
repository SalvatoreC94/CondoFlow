<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'condominium_id' => $this->condominium_id,
            'name' => $this->name,
            'code' => $this->code,
            'floors_count' => $this->floors_count,
            'units_count' => $this->whenCounted('units'),
        ];
    }
}
