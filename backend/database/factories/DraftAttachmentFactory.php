<?php

namespace Database\Factories;

use App\Models\Prd;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DraftAttachment>
 */
class DraftAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = Str::uuid() . '.txt';
        
        return [
            'id' => Str::uuid(),
            'prd_id' => Prd::factory(),
            'filename' => $filename,
            'original_filename' => fake()->word() . '.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => fake()->numberBetween(100, 10000),
            'extracted_text' => fake()->paragraph(),
            'status' => 'ready',
        ];
    }

    /**
     * Indicate that the attachment is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'extracted_text' => null,
        ]);
    }

    /**
     * Indicate that the attachment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'extracted_text' => null,
            'error_message' => 'Failed to process file',
        ]);
    }
}
