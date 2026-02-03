<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prd>
 */
class PrdFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $id = Str::uuid()->toString();
        
        return [
            'id' => $id,
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'file_path' => fn (array $attributes) => 
                "storage/prds/{$attributes['user_id']}/{$id}.md",
            'status' => 'draft',
            'estimated_tokens' => fake()->numberBetween(100, 5000),
        ];
    }

    /**
     * Indicate that the PRD is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the PRD is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    /**
     * Indicate the PRD belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'file_path' => "storage/prds/{$user->id}/{$attributes['id']}.md",
        ]);
    }
}
