<?php

namespace Database\Factories;

use App\Enums\LocationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
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
}
