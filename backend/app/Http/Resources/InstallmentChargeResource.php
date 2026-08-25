<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentChargeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'installment_id' => $this->installment_id,
            'installment' => new InstallmentResource($this->whenLoaded('installment')),
            'unit' => new UnitResource($this->whenLoaded('unit')),
            'amount' => $this->amount,
            'paid' => $this->paid,
            'paid_at' => $this->paid_at,
            'notes' => $this->notes,
        ];
    }
}
