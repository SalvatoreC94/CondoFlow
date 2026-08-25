<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssemblyResolutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assembly_id' => $this->assembly_id,
            'description' => $this->description,
            'outcome' => $this->outcome->value,
            'outcome_label' => $this->outcome->label(),
            'notes' => $this->notes,
            'sort_order' => $this->sort_order,
        ];
    }
}
