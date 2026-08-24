<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'phone' => $this->phone,
            'email' => $this->email,
            'contact_name' => $this->contact_name,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'condominiums' => CondominiumResource::collection($this->whenLoaded('condominiums')),
            'contacts' => SupplierContactResource::collection($this->whenLoaded('contacts')),
        ];
    }
}
