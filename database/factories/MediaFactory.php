<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'disk' => 'public',
            'path' => 'uploads/1/'.now()->format('Y/m').'/'.Str::uuid().'.jpg',
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(1024, 5 * 1024 * 1024),
        ];
    }

    /**
     * Indicate the media is a video.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'path' => 'uploads/1/'.now()->format('Y/m').'/'.Str::uuid().'.mp4',
            'original_name' => fake()->word().'.mp4',
            'mime_type' => 'video/mp4',
            'size' => fake()->numberBetween(1024, 50 * 1024 * 1024),
        ]);
    }

    /**
     * Indicate the media is a PDF document.
     */
    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'path' => 'uploads/1/'.now()->format('Y/m').'/'.Str::uuid().'.pdf',
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1024, 10 * 1024 * 1024),
        ]);
    }
}
