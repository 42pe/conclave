<?php

namespace Database\Seeders\Development;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    /**
     * Seed development conversations with messages.
     */
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first();
        $moderator = User::where('username', 'moderator')->first();
        $testUser = User::where('username', 'testuser')->first();
        $minimal = User::where('username', 'minimal')->first();

        if (! $admin || ! $moderator || ! $testUser || ! $minimal) {
            return;
        }

        // Conversation 1: Admin <-> Test User (with unread messages)
        $conv1 = Conversation::create();
        $conv1->participants()->createMany([
            ['user_id' => $admin->id, 'last_read_at' => now()->subHours(2)],
            ['user_id' => $testUser->id, 'last_read_at' => now()],
        ]);

        Message::create([
            'conversation_id' => $conv1->id,
            'user_id' => $admin->id,
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Hey, welcome to the forum! Let me know if you need help getting started.']]]],
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        Message::create([
            'conversation_id' => $conv1->id,
            'user_id' => $testUser->id,
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Thanks! The forum looks great. I have a question about topics.']]]],
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        // Unread message for admin
        Message::create([
            'conversation_id' => $conv1->id,
            'user_id' => $testUser->id,
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Actually, I figured it out. Never mind!']]]],
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        // Conversation 2: Moderator <-> Test User (all read)
        $conv2 = Conversation::create();
        $conv2->participants()->createMany([
            ['user_id' => $moderator->id, 'last_read_at' => now()],
            ['user_id' => $testUser->id, 'last_read_at' => now()],
        ]);

        Message::create([
            'conversation_id' => $conv2->id,
            'user_id' => $moderator->id,
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Just a heads up: I moved your discussion to a more appropriate topic.']]]],
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        Message::create([
            'conversation_id' => $conv2->id,
            'user_id' => $testUser->id,
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'No problem, thanks for letting me know.']]]],
            'created_at' => now()->subDay()->addMinutes(5),
            'updated_at' => now()->subDay()->addMinutes(5),
        ]);

        // Conversation 3: Admin <-> Minimal User (unread for minimal)
        $conv3 = Conversation::create();
        $conv3->participants()->createMany([
            ['user_id' => $admin->id, 'last_read_at' => now()],
            ['user_id' => $minimal->id, 'last_read_at' => null],
        ]);

        Message::create([
            'conversation_id' => $conv3->id,
            'user_id' => $admin->id,
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Hi! Please fill out your profile to help others in the community get to know you.']]]],
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
    }
}
