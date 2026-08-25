<?php

namespace Database\Factories;

use App\Models\Installment;
use App\Models\InstallmentCharge;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstallmentCharge>
 */
class InstallmentChargeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'installment_id' => Installment::factory(),
            'unit_id' => Unit::factory(),
            'amount' => fake()->randomFloat(2, 50, 500),
            'paid' => false,
            'paid_at' => null,
            'notes' => null,
        ];
    }
}
