<?php

namespace Database\Seeders\Development;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    /**
     * Seed development topics with varied visibility levels.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        $topics = [
            [
                'title' => 'General Discussion',
                'description' => 'A place for open conversation about anything and everything.',
                'icon' => 'MessageCircle',
                'visibility' => 'public',
                'sort_order' => 0,
            ],
            [
                'title' => 'Technology & Code',
                'description' => 'Discussions about programming, software development, and tech news.',
                'icon' => 'Code',
                'visibility' => 'public',
                'sort_order' => 1,
            ],
            [
                'title' => 'Career & Professional',
                'description' => 'Career advice, job postings, and professional development.',
                'icon' => 'Briefcase',
                'visibility' => 'public',
                'sort_order' => 2,
            ],
            [
                'title' => 'Community Events',
                'description' => 'Announcements and discussions about community meetups and events.',
                'icon' => 'Calendar',
                'visibility' => 'public',
                'sort_order' => 3,
            ],
            [
                'title' => 'Members Only Lounge',
                'description' => 'Exclusive discussions for verified community members.',
                'icon' => 'Lock',
                'visibility' => 'restricted',
                'sort_order' => 4,
            ],
            [
                'title' => 'Admin Announcements',
                'description' => 'Official announcements from the administration team.',
                'icon' => 'Megaphone',
                'visibility' => 'private',
                'sort_order' => 5,
            ],
        ];

        foreach ($topics as $topicData) {
            Topic::create([
                ...$topicData,
                'created_by' => $admin->id,
            ]);
        }
    }
}
