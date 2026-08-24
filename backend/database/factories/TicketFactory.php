<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Condominium;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'condominium_id' => Condominium::factory(),
            'unit_id' => null,
            'ticket_category_id' => TicketCategory::factory(),
            'created_by' => User::factory(),
            'assigned_caretaker_id' => null,
            'supplier_id' => null,
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'status' => TicketStatus::New,
            'location' => fake()->optional()->word(),
        ];
    }

    public function withStatus(TicketStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'resolved_at' => in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true)
                ? fake()->dateTimeBetween('-30 days', 'now')
                : null,
            'closed_at' => $status === TicketStatus::Closed
                ? fake()->dateTimeBetween('-15 days', 'now')
                : null,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TicketPriority::Urgent,
        ]);
    }
}
