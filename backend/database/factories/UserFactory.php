<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'google_id' => fake()->unique()->numerify('####################'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'avatar_url' => fake()->imageUrl(100, 100, 'people'),
            'google_access_token' => Str::random(100),
            'google_refresh_token' => Str::random(100),
            'google_token_expires_at' => now()->addHour(),
            'preferred_language' => 'en',
            'tier' => 'free',
            'tier_expires_at' => null,
            'stripe_customer_id' => null,
        ];
    }

    /**
     * Indicate that the user is on the pro tier.
     */
    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier' => 'pro',
        ]);
    }

    /**
     * Indicate that the user is on the team tier.
     */
    public function team(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier' => 'team',
        ]);
    }

    /**
     * Indicate that the user is on the enterprise tier.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier' => 'enterprise',
        ]);
    }

    /**
     * Indicate that the user has an expired Google token.
     */
    public function expiredToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'google_token_expires_at' => now()->subHour(),
        ]);
    }
}
