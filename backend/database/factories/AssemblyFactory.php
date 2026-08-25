<?php

namespace Database\Factories;

use App\Enums\AssemblyStatus;
use App\Enums\AssemblyType;
use App\Models\Assembly;
use App\Models\Condominium;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assembly>
 */
class AssemblyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'condominium_id' => Condominium::factory(),
            'created_by' => User::factory()->administrator(),
            'title' => 'Assemblea '.fake()->monthName(),
            'type' => AssemblyType::Ordinary,
            'status' => AssemblyStatus::Scheduled,
            'agenda' => fake()->paragraph(),
            'location' => fake()->optional()->address(),
            'scheduled_at' => fake()->dateTimeBetween('now', '+2 months'),
        ];
    }
}
