<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('3#########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Condomino,
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function administrator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Administrator,
        ]);
    }

    public function caretaker(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Caretaker,
        ]);
    }

    public function condomino(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Condomino,
        ]);
    }

    public function invited(): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => null,
            'email_verified_at' => null,
            'status' => UserStatus::Invited,
            'invitation_token' => Str::random(48),
            'invitation_expires_at' => now()->addDays(7),
        ]);
    }
}
