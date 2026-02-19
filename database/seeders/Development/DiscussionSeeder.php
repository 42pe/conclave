<?php

namespace Database\Seeders\Development;

use App\Models\Discussion;
use App\Models\Location;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class DiscussionSeeder extends Seeder
{
    /**
     * Seed development discussions across topics.
     */
    public function run(): void
    {
        $users = User::whereIn('email', [
            'admin@example.com',
            'moderator@example.com',
            'test@example.com',
            'verbose@example.com',
        ])->get();

        $topics = Topic::all();
        $locations = Location::active()->get();

        $generalTopic = $topics->firstWhere('slug', 'general-discussion');
        $techTopic = $topics->firstWhere('slug', 'technology-code');
        $careerTopic = $topics->firstWhere('slug', 'career-professional');
        $eventsTopic = $topics->firstWhere('slug', 'community-events');

        // General Discussion — pinned welcome post
        if ($generalTopic) {
            Discussion::create([
                'topic_id' => $generalTopic->id,
                'user_id' => $users->firstWhere('email', 'admin@example.com')->id,
                'title' => 'Welcome to Conclave',
                'body' => [
                    ['type' => 'heading-one', 'children' => [['text' => 'Welcome to the Conclave Forum!']]],
                    ['type' => 'paragraph', 'children' => [['text' => 'This is the official community space for open conversation. Please be respectful and follow the community guidelines.']]],
                    ['type' => 'paragraph', 'children' => [['text' => 'Feel free to introduce yourself in this thread.']]],
                ],
                'is_pinned' => true,
            ]);

            Discussion::create([
                'topic_id' => $generalTopic->id,
                'user_id' => $users->firstWhere('email', 'test@example.com')->id,
                'title' => 'What are you working on this week?',
                'body' => [
                    ['type' => 'paragraph', 'children' => [['text' => 'I\'d love to hear what everyone is focused on. I\'m currently building a REST API for a small side project.']]],
                ],
                'reply_count' => 3,
                'last_reply_at' => now()->subHours(2),
            ]);

            Discussion::create([
                'topic_id' => $generalTopic->id,
                'user_id' => $users->firstWhere('email', 'verbose@example.com')->id,
                'location_id' => $locations->firstWhere('iso_code', 'US-CA')?->id,
                'title' => 'Best coffee shops for working remotely in California',
                'body' => [
                    ['type' => 'paragraph', 'children' => [['text' => 'Looking for recommendations on great coffee shops in California that are remote-work friendly. Good WiFi and power outlets are a must!']]],
                    ['type' => 'bulleted-list', 'children' => [
                        ['type' => 'list-item', 'children' => [['text' => 'Stable WiFi']]],
                        ['type' => 'list-item', 'children' => [['text' => 'Power outlets at tables']]],
                        ['type' => 'list-item', 'children' => [['text' => 'Quiet atmosphere']]],
                    ]],
                ],
            ]);
        }

        // Technology & Code
        if ($techTopic) {
            Discussion::create([
                'topic_id' => $techTopic->id,
                'user_id' => $users->firstWhere('email', 'test@example.com')->id,
                'title' => 'Laravel 12 first impressions',
                'body' => [
                    ['type' => 'paragraph', 'children' => [['text' => 'I\'ve been using Laravel 12 for a few weeks now. The new streamlined structure is really nice.']]],
                    ['type' => 'paragraph', 'children' => [['text' => 'What are your favorite new features?', 'bold' => true]]],
                ],
                'reply_count' => 7,
                'last_reply_at' => now()->subMinutes(30),
            ]);

            Discussion::create([
                'topic_id' => $techTopic->id,
                'user_id' => $users->firstWhere('email', 'moderator@example.com')->id,
                'title' => 'React 19 Server Components discussion',
                'body' => [
                    ['type' => 'paragraph', 'children' => [['text' => 'Let\'s talk about React 19 and the server components pattern. How is everyone adapting?']]],
                ],
                'is_pinned' => true,
                'reply_count' => 12,
                'last_reply_at' => now()->subHour(),
            ]);

            Discussion::create([
                'topic_id' => $techTopic->id,
                'user_id' => $users->firstWhere('email', 'verbose@example.com')->id,
                'title' => 'TypeScript best practices for large codebases',
                'body' => [
                    ['type' => 'paragraph', 'children' => [['text' => 'After working on several large TypeScript projects, here are patterns I\'ve found most valuable:']]],
                    ['type' => 'numbered-list', 'children' => [
                        ['type' => 'list-item', 'children' => [['text' => 'Use strict mode everywhere']]],
                        ['type' => 'list-item', 'children' => [['text' => 'Prefer interfaces over types for object shapes']]],
                        ['type' => 'list-item', 'children' => [['text' => 'Avoid any at all costs']]],
                    ]],
                ],
            ]);
        }

        // Career & Professional
        if ($careerTopic) {
            Discussion::create([
                'topic_id' => $careerTopic->id,
                'user_id' => $users->firstWhere('email', 'moderator@example.com')->id,
                'location_id' => $locations->firstWhere('iso_code', 'US-NY')?->id,
                'title' => 'Senior developer job opportunities in New York',
                'body' => [
                    ['type' => 'paragraph', 'children' => [['text' => 'Several companies in the NYC area are hiring senior developers. Feel free to share openings here.']]],
                ],
                'reply_count' => 5,
                'last_reply_at' => now()->subHours(6),
            ]);
        }

        // Community Events
        if ($eventsTopic) {
            Discussion::create([
                'topic_id' => $eventsTopic->id,
                'user_id' => $users->firstWhere('email', 'admin@example.com')->id,
                'title' => 'Monthly community meetup - March 2026',
                'body' => [
                    ['type' => 'heading-two', 'children' => [['text' => 'March Community Meetup']]],
                    ['type' => 'paragraph', 'children' => [['text' => 'Join us for our monthly community meetup! We\'ll discuss the latest projects and share knowledge.']]],
                    ['type' => 'paragraph', 'children' => [['text' => 'Date: March 15, 2026', 'bold' => true]]],
                    ['type' => 'paragraph', 'children' => [['text' => 'RSVP in the replies below.']]],
                ],
                'is_pinned' => true,
            ]);
        }
    }
}
