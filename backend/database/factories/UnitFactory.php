<?php

namespace Database\Factories;

use App\Enums\UnitType;
use App\Models\Condominium;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'condominium_id' => Condominium::factory(),
            'building_id' => null,
            'code' => strtoupper(fake()->bothify('?/##')),
            'floor' => (string) fake()->numberBetween(0, 6),
            'type' => UnitType::Apartment,
            'surface_sqm' => fake()->randomFloat(2, 40, 160),
            'notes' => null,
        ];
    }
}
