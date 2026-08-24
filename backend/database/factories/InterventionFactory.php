<?php

namespace Database\Factories;

use App\Models\Intervention;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intervention>
 */
class InterventionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'supplier_id' => null,
            'caretaker_id' => null,
            'scheduled_at' => fake()->dateTimeBetween('-10 days', '+10 days'),
            'notes' => fake()->optional()->sentence(),
            'cost' => fake()->optional()->randomFloat(2, 30, 800),
        ];
    }
}
