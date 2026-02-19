<?php

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed development data for manual review and browser testing.
     *
     * All seeded users use 'password' as their password.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            LocationSeeder::class,
            TopicSeeder::class,
            // Future phases:
            // DiscussionSeeder::class,  // Phase 5
            // ReplySeeder::class,       // Phase 6
            // ConversationSeeder::class, // Phase 9
        ]);
    }
}
