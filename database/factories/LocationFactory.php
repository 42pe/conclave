<?php

namespace Database\Factories;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->state(),
            'iso_code' => fake()->unique()->regexify('[A-Z]{2}-[A-Z]{2}'),
            'type' => LocationType::UsState,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the location is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the location is a country.
     */
    public function country(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LocationType::Country,
            'name' => fake()->country(),
            'iso_code' => fake()->unique()->regexify('[A-Z]{2}'),
        ]);
    }

    /**
     * Indicate that the location is "Any".
     */
    public function any(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LocationType::Any,
            'name' => 'Any',
            'iso_code' => 'ANY',
        ]);
    }
}
