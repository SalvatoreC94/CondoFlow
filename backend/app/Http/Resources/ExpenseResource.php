<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'condominium_id' => $this->condominium_id,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'category' => $this->category,
            'description' => $this->description,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date?->toDateString(),
            'notes' => $this->notes,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
        ];
    }
}
