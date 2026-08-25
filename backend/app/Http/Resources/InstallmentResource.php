<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'condominium_id' => $this->condominium_id,
            'title' => $this->title,
            'description' => $this->description,
            'total_amount' => $this->total_amount,
            'split_method' => $this->split_method->value,
            'split_method_label' => $this->split_method->label(),
            'due_date' => $this->due_date?->toDateString(),
            'units_count' => $this->whenLoaded('charges', fn () => $this->charges->count()),
            'paid_units_count' => $this->whenLoaded('charges', fn () => $this->charges->where('paid', true)->count()),
            'paid_amount' => $this->whenLoaded('charges', fn () => (string) $this->charges->where('paid', true)->sum('amount')),
            'charges' => InstallmentChargeResource::collection($this->whenLoaded('charges')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
        ];
    }
}
