<?php

namespace Database\Factories;

use App\Models\Condominium;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'condominium_id' => Condominium::factory(),
            'supplier_id' => null,
            'created_by' => User::factory()->administrator(),
            'category' => fake()->randomElement(['Manutenzione ordinaria', 'Pulizie', 'Giardinaggio', 'Utenze', 'Assicurazione']),
            'description' => fake()->sentence(6),
            'amount' => fake()->randomFloat(2, 50, 3000),
            'expense_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
