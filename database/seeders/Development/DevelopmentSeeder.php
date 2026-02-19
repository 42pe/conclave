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
            DiscussionSeeder::class,
            ReplySeeder::class,
            BannedEmailSeeder::class,
            // Future phases:
            // ConversationSeeder::class, // Phase 9
        ]);
    }
}
