<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
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
        $userId = fake()->numberBetween(1, 100);

        return [
            'user_id' => User::factory(),
            'disk' => 'public',
            'path' => sprintf('uploads/%d/%d/%02d/%s.jpg', $userId, now()->year, now()->month, fake()->uuid()),
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(1024, 5_242_880),
        ];
    }

    /**
     * Media is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
        ]);
    }

    /**
     * Media is a video.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'original_name' => fake()->word().'.mp4',
            'mime_type' => 'video/mp4',
            'size' => fake()->numberBetween(1_048_576, 52_428_800),
        ]);
    }

    /**
     * Media is a document.
     */
    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    /**
     * Media is orphaned (not attached to any model).
     */
    public function orphaned(): static
    {
        return $this->state(fn (array $attributes) => [
            'mediable_type' => null,
            'mediable_id' => null,
        ]);
    }
}
