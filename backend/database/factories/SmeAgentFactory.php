<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SmeAgent>
 */
class SmeAgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->jobTitle() . ' Expert';
        
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'description' => fake()->sentence(),
            'expertise' => fake()->paragraph(),
            'system_prompt' => 'You are an expert in ' . fake()->bs() . '. Provide detailed technical guidance.',
            'icon' => null,
            'category' => fake()->randomElement(['general', 'technical', 'business', 'design']),
            'is_public' => false,
            'is_system' => false,
            'usage_count' => 0,
        ];
    }

    /**
     * Create a public agent.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Create a system agent.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'is_system' => true,
            'is_public' => true,
        ]);
    }
}
