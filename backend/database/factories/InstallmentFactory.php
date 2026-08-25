<?php

namespace Database\Factories;

use App\Enums\SplitMethod;
use App\Models\Condominium;
use App\Models\Installment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Installment>
 */
class InstallmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'condominium_id' => Condominium::factory(),
            'created_by' => User::factory()->administrator(),
            'title' => 'Rata '.fake()->monthName(),
            'description' => fake()->optional()->sentence(),
            'total_amount' => fake()->randomFloat(2, 1000, 30000),
            'split_method' => SplitMethod::Equal,
            'due_date' => fake()->dateTimeBetween('now', '+3 months'),
        ];
    }
}
