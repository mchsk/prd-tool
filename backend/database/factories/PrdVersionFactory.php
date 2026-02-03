<?php

namespace Database\Factories;

use App\Models\Prd;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PrdVersion>
 */
class PrdVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $content = fake()->paragraphs(3, true);
        
        return [
            'id' => Str::uuid(),
            'prd_id' => Prd::factory(),
            'created_by' => User::factory(),
            'version_number' => 1,
            'title' => fake()->sentence(4),
            'content' => $content,
            'content_hash' => md5($content),
            'content_size' => strlen($content),
            'change_summary' => fake()->optional()->sentence(),
            'change_source' => fake()->randomElement(['manual', 'auto', 'ai']),
        ];
    }
}
