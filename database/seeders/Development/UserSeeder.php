<?php

namespace Database\Seeders\Development;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed development users across all roles and states.
     */
    public function run(): void
    {
        // Primary admin — full profile
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'first_name' => 'Sarah',
            'last_name' => 'Chen',
            'preferred_name' => null,
            'bio' => 'Forum administrator. Keeping the community safe and productive.',
        ]);

        // Moderator — has preferred name
        User::factory()->moderator()->create([
            'name' => 'Moderator User',
            'username' => 'moderator',
            'email' => 'moderator@example.com',
            'first_name' => 'James',
            'last_name' => 'Wilson',
            'preferred_name' => 'Jim',
            'bio' => 'Community moderator. Here to help keep discussions on track.',
        ]);

        // Regular active user — full profile with bio
        User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Alex',
            'last_name' => 'Rivera',
            'preferred_name' => null,
            'bio' => 'Software developer and forum enthusiast. I enjoy long discussions about architecture patterns.',
        ]);

        // Regular user — minimal profile (no optional fields)
        User::factory()->create([
            'name' => 'Minimal User',
            'username' => 'minimal',
            'email' => 'minimal@example.com',
            'first_name' => null,
            'last_name' => null,
            'preferred_name' => null,
            'bio' => null,
        ]);

        // User with a long username and long bio (edge case)
        User::factory()->create([
            'name' => 'Verbose McTalkative',
            'username' => 'verbose-talker99',
            'email' => 'verbose@example.com',
            'first_name' => 'Verbose',
            'last_name' => 'McTalkative',
            'preferred_name' => 'Verby',
            'bio' => 'I have a lot to say about everything. From the intricacies of distributed systems to the perfect way to brew coffee, I have opinions and I am not afraid to share them. This bio tests how long text renders in the UI across different screen sizes and contexts.',
        ]);

        // Suspended user
        User::factory()->suspended()->create([
            'name' => 'Suspended User',
            'username' => 'suspended',
            'email' => 'suspended@example.com',
            'first_name' => 'Pat',
            'last_name' => 'Trouble',
            'bio' => 'This account has been suspended.',
        ]);

        // Deleted user — content should show "Deleted User"
        User::factory()->deleted()->create([
            'name' => 'Former Member',
            'username' => 'deleted-user',
            'email' => 'deleted@example.com',
            'first_name' => 'Ghost',
            'last_name' => 'User',
            'preferred_name' => 'Casper',
            'bio' => 'This should not be visible since the user is deleted.',
        ]);

        // Unverified user — email not verified
        User::factory()->unverified()->create([
            'name' => 'Unverified User',
            'username' => 'unverified',
            'email' => 'unverified@example.com',
            'first_name' => 'New',
            'last_name' => 'Member',
            'bio' => null,
        ]);

        // Privacy-conscious user — hides real name and email
        User::factory()->create([
            'name' => 'Private Person',
            'username' => 'private-user',
            'email' => 'private@example.com',
            'first_name' => 'Secret',
            'last_name' => 'Identity',
            'preferred_name' => null,
            'bio' => 'I value my privacy.',
            'show_real_name' => false,
            'show_email' => false,
            'show_in_directory' => false,
        ]);

        // A batch of regular users for a populated feel
        User::factory(10)->create();
    }
}
