<?php

namespace Database\Factories;

use App\Models\Prd;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PrdComment>
 */
class PrdCommentFactory extends Factory
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
            'user_id' => User::factory(),
            'content' => fake()->paragraph(),
            'line_number' => fake()->optional()->numberBetween(1, 100),
            'is_resolved' => false,
        ];
    }

    /**
     * Indicate that the comment is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_resolved' => true,
        ]);
    }

    /**
     * Indicate that the comment is anonymous.
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'author_name' => fake()->name(),
        ]);
    }
}
