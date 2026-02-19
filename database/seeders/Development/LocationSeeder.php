<?php

namespace Database\Seeders\Development;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Seed development locations: "Any", 50 US states, and select countries.
     */
    public function run(): void
    {
        $locations = [
            // Any location
            ['name' => 'Any', 'iso_code' => 'ANY', 'type' => 'any', 'sort_order' => 0],

            // US States
            ['name' => 'Alabama', 'iso_code' => 'US-AL', 'type' => 'us_state', 'sort_order' => 1],
            ['name' => 'Alaska', 'iso_code' => 'US-AK', 'type' => 'us_state', 'sort_order' => 2],
            ['name' => 'Arizona', 'iso_code' => 'US-AZ', 'type' => 'us_state', 'sort_order' => 3],
            ['name' => 'Arkansas', 'iso_code' => 'US-AR', 'type' => 'us_state', 'sort_order' => 4],
            ['name' => 'California', 'iso_code' => 'US-CA', 'type' => 'us_state', 'sort_order' => 5],
            ['name' => 'Colorado', 'iso_code' => 'US-CO', 'type' => 'us_state', 'sort_order' => 6],
            ['name' => 'Connecticut', 'iso_code' => 'US-CT', 'type' => 'us_state', 'sort_order' => 7],
            ['name' => 'Delaware', 'iso_code' => 'US-DE', 'type' => 'us_state', 'sort_order' => 8],
            ['name' => 'Florida', 'iso_code' => 'US-FL', 'type' => 'us_state', 'sort_order' => 9],
            ['name' => 'Georgia', 'iso_code' => 'US-GA', 'type' => 'us_state', 'sort_order' => 10],
            ['name' => 'Hawaii', 'iso_code' => 'US-HI', 'type' => 'us_state', 'sort_order' => 11],
            ['name' => 'Idaho', 'iso_code' => 'US-ID', 'type' => 'us_state', 'sort_order' => 12],
            ['name' => 'Illinois', 'iso_code' => 'US-IL', 'type' => 'us_state', 'sort_order' => 13],
            ['name' => 'Indiana', 'iso_code' => 'US-IN', 'type' => 'us_state', 'sort_order' => 14],
            ['name' => 'Iowa', 'iso_code' => 'US-IA', 'type' => 'us_state', 'sort_order' => 15],
            ['name' => 'Kansas', 'iso_code' => 'US-KS', 'type' => 'us_state', 'sort_order' => 16],
            ['name' => 'Kentucky', 'iso_code' => 'US-KY', 'type' => 'us_state', 'sort_order' => 17],
            ['name' => 'Louisiana', 'iso_code' => 'US-LA', 'type' => 'us_state', 'sort_order' => 18],
            ['name' => 'Maine', 'iso_code' => 'US-ME', 'type' => 'us_state', 'sort_order' => 19],
            ['name' => 'Maryland', 'iso_code' => 'US-MD', 'type' => 'us_state', 'sort_order' => 20],
            ['name' => 'Massachusetts', 'iso_code' => 'US-MA', 'type' => 'us_state', 'sort_order' => 21],
            ['name' => 'Michigan', 'iso_code' => 'US-MI', 'type' => 'us_state', 'sort_order' => 22],
            ['name' => 'Minnesota', 'iso_code' => 'US-MN', 'type' => 'us_state', 'sort_order' => 23],
            ['name' => 'Mississippi', 'iso_code' => 'US-MS', 'type' => 'us_state', 'sort_order' => 24],
            ['name' => 'Missouri', 'iso_code' => 'US-MO', 'type' => 'us_state', 'sort_order' => 25],
            ['name' => 'Montana', 'iso_code' => 'US-MT', 'type' => 'us_state', 'sort_order' => 26],
            ['name' => 'Nebraska', 'iso_code' => 'US-NE', 'type' => 'us_state', 'sort_order' => 27],
            ['name' => 'Nevada', 'iso_code' => 'US-NV', 'type' => 'us_state', 'sort_order' => 28],
            ['name' => 'New Hampshire', 'iso_code' => 'US-NH', 'type' => 'us_state', 'sort_order' => 29],
            ['name' => 'New Jersey', 'iso_code' => 'US-NJ', 'type' => 'us_state', 'sort_order' => 30],
            ['name' => 'New Mexico', 'iso_code' => 'US-NM', 'type' => 'us_state', 'sort_order' => 31],
            ['name' => 'New York', 'iso_code' => 'US-NY', 'type' => 'us_state', 'sort_order' => 32],
            ['name' => 'North Carolina', 'iso_code' => 'US-NC', 'type' => 'us_state', 'sort_order' => 33],
            ['name' => 'North Dakota', 'iso_code' => 'US-ND', 'type' => 'us_state', 'sort_order' => 34],
            ['name' => 'Ohio', 'iso_code' => 'US-OH', 'type' => 'us_state', 'sort_order' => 35],
            ['name' => 'Oklahoma', 'iso_code' => 'US-OK', 'type' => 'us_state', 'sort_order' => 36],
            ['name' => 'Oregon', 'iso_code' => 'US-OR', 'type' => 'us_state', 'sort_order' => 37],
            ['name' => 'Pennsylvania', 'iso_code' => 'US-PA', 'type' => 'us_state', 'sort_order' => 38],
            ['name' => 'Rhode Island', 'iso_code' => 'US-RI', 'type' => 'us_state', 'sort_order' => 39],
            ['name' => 'South Carolina', 'iso_code' => 'US-SC', 'type' => 'us_state', 'sort_order' => 40],
            ['name' => 'South Dakota', 'iso_code' => 'US-SD', 'type' => 'us_state', 'sort_order' => 41],
            ['name' => 'Tennessee', 'iso_code' => 'US-TN', 'type' => 'us_state', 'sort_order' => 42],
            ['name' => 'Texas', 'iso_code' => 'US-TX', 'type' => 'us_state', 'sort_order' => 43],
            ['name' => 'Utah', 'iso_code' => 'US-UT', 'type' => 'us_state', 'sort_order' => 44],
            ['name' => 'Vermont', 'iso_code' => 'US-VT', 'type' => 'us_state', 'sort_order' => 45],
            ['name' => 'Virginia', 'iso_code' => 'US-VA', 'type' => 'us_state', 'sort_order' => 46],
            ['name' => 'Washington', 'iso_code' => 'US-WA', 'type' => 'us_state', 'sort_order' => 47],
            ['name' => 'West Virginia', 'iso_code' => 'US-WV', 'type' => 'us_state', 'sort_order' => 48],
            ['name' => 'Wisconsin', 'iso_code' => 'US-WI', 'type' => 'us_state', 'sort_order' => 49],
            ['name' => 'Wyoming', 'iso_code' => 'US-WY', 'type' => 'us_state', 'sort_order' => 50],

            // Countries
            ['name' => 'Canada', 'iso_code' => 'CA', 'type' => 'country', 'sort_order' => 51],
            ['name' => 'Mexico', 'iso_code' => 'MX', 'type' => 'country', 'sort_order' => 52],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}
