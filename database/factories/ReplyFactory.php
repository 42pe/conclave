<?php

namespace Database\Factories;

use App\Models\Discussion;
use App\Models\Reply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reply>
 */
class ReplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discussion_id' => Discussion::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'depth' => 0,
            'body' => [
                [
                    'type' => 'paragraph',
                    'children' => [['text' => fake()->paragraph()]],
                ],
            ],
        ];
    }

    /**
     * Indicate that the reply is nested (depth 1 with parent).
     */
    public function nested(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => Reply::factory()->state([
                'discussion_id' => $attributes['discussion_id'],
            ]),
            'depth' => 1,
        ]);
    }

    /**
     * Indicate that the reply is deeply nested (depth 2 with parent).
     */
    public function deeplyNested(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => Reply::factory()->nested()->state([
                'discussion_id' => $attributes['discussion_id'],
            ]),
            'depth' => 2,
        ]);
    }
}
