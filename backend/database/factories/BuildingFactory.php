<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Condominium;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'condominium_id' => Condominium::factory(),
            'name' => 'Scala '.fake()->randomLetter(),
            'code' => strtoupper(fake()->lexify('??')),
            'floors_count' => fake()->numberBetween(2, 8),
        ];
    }
}
