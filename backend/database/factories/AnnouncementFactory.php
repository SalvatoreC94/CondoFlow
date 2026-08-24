<?php

namespace Database\Factories;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementPriority;
use App\Models\Announcement;
use App\Models\Condominium;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'condominium_id' => Condominium::factory(),
            'created_by' => User::factory()->administrator(),
            'title' => fake()->sentence(5),
            'content' => fake()->paragraphs(2, true),
            'priority' => fake()->randomElement(AnnouncementPriority::cases()),
            'audience' => AnnouncementAudience::All,
            'published_at' => now(),
            'expires_at' => null,
        ];
    }
}
