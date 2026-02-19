<?php

namespace Database\Factories;

use App\Enums\TopicVisibility;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(3),
            'description' => fake()->paragraph(),
            'icon' => fake()->randomElement(['MessageCircle', 'Code', 'Briefcase', 'Heart', 'Star', 'Zap']),
            'visibility' => TopicVisibility::Public,
            'sort_order' => 0,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the topic is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => TopicVisibility::Public,
        ]);
    }

    /**
     * Indicate that the topic is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => TopicVisibility::Private,
        ]);
    }

    /**
     * Indicate that the topic is restricted.
     */
    public function restricted(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => TopicVisibility::Restricted,
        ]);
    }
}
