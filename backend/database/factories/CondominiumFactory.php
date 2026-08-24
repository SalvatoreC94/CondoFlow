<?php

namespace Database\Factories;

use App\Models\Condominium;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Condominium>
 */
class CondominiumFactory extends Factory
{
    public function definition(): array
    {
        return [
            'administrator_id' => User::factory()->administrator(),
            'name' => 'Condominio '.fake()->streetName(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'country' => 'IT',
            'total_units' => fake()->numberBetween(10, 60),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
