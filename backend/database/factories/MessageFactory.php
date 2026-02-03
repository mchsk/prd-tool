<?php

namespace Database\Factories;

use App\Models\Prd;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
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
            'prd_id' => Prd::factory(),
            'role' => fake()->randomElement(['user', 'assistant']),
            'content' => fake()->paragraph(),
            'prd_update_suggestion' => null,
            'update_applied' => false,
            'token_count' => fake()->numberBetween(10, 500),
        ];
    }

    /**
     * Indicate that the message is from the user.
     */
    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
        ]);
    }

    /**
     * Indicate that the message is from the assistant.
     */
    public function assistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'assistant',
        ]);
    }

    /**
     * Indicate that the message has a PRD update suggestion.
     */
    public function withUpdate(): static
    {
        return $this->state(fn (array $attributes) => [
            'prd_update_suggestion' => fake()->paragraph(),
            'role' => 'assistant',
        ]);
    }
}
