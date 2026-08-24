<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'administrator_id' => User::factory()->administrator(),
            'name' => fake()->company(),
            'category' => fake()->randomElement([
                'Idraulica', 'Elettricista', 'Ascensori', 'Giardinaggio',
                'Pulizie', 'Piscine', 'Sicurezza', 'Edilizia', 'Impianti', 'Altro',
            ]),
            'phone' => fake()->numerify('0## #######'),
            'email' => fake()->companyEmail(),
            'contact_name' => fake()->name(),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
