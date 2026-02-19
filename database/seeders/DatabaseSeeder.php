<?php

namespace Database\Seeders;

use Database\Seeders\Development\DevelopmentSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('local', 'testing', 'staging')) {
            $this->call(DevelopmentSeeder::class);
        }
    }
}
