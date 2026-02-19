<?php

namespace Database\Factories;

use App\Enums\TopicVisibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Topic>
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
            'title' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'icon' => fake()->randomElement(['star', 'heart', 'globe', 'code', 'users', 'flame', 'rocket', 'lightbulb']),
            'header_image_path' => null,
            'visibility' => TopicVisibility::Public,
            'sort_order' => 0,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Topic with private visibility.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => TopicVisibility::Private,
        ]);
    }

    /**
     * Topic with restricted visibility.
     */
    public function restricted(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => TopicVisibility::Restricted,
        ]);
    }
}
