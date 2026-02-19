<?php

namespace Database\Factories;

use App\Models\Discussion;
use App\Models\Location;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discussion>
 */
class DiscussionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'body' => [
                [
                    'type' => 'paragraph',
                    'children' => [['text' => fake()->paragraph()]],
                ],
            ],
            'is_pinned' => false,
            'is_locked' => false,
            'reply_count' => 0,
            'last_reply_at' => null,
        ];
    }

    /**
     * Indicate that the discussion is pinned.
     */
    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    /**
     * Indicate that the discussion is locked.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_locked' => true,
        ]);
    }

    /**
     * Indicate that the discussion has a location.
     */
    public function withLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_id' => Location::factory(),
        ]);
    }
}
