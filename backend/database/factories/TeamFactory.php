<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();
        
        return [
            'id' => Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'owner_id' => User::factory(),
            'description' => fake()->optional()->sentence(),
            'max_members' => 5,
        ];
    }
}
