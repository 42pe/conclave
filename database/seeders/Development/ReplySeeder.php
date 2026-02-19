<?php

namespace Database\Seeders\Development;

use App\Models\Discussion;
use App\Models\Reply;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReplySeeder extends Seeder
{
    /**
     * Seed development replies across discussions.
     */
    public function run(): void
    {
        $users = User::whereIn('email', [
            'admin@example.com',
            'moderator@example.com',
            'test@example.com',
            'verbose@example.com',
        ])->get();

        $discussions = Discussion::all();

        foreach ($discussions as $discussion) {
            $replyCount = 0;

            // Create 2-4 top-level replies per discussion
            $topLevelCount = fake()->numberBetween(2, 4);
            for ($i = 0; $i < $topLevelCount; $i++) {
                $topReply = Reply::create([
                    'discussion_id' => $discussion->id,
                    'user_id' => $users->random()->id,
                    'parent_id' => null,
                    'depth' => 0,
                    'body' => [
                        ['type' => 'paragraph', 'children' => [['text' => fake()->paragraph()]]],
                    ],
                ]);
                $replyCount++;

                // 50% chance of a depth-1 reply
                if (fake()->boolean(50)) {
                    $childReply = Reply::create([
                        'discussion_id' => $discussion->id,
                        'user_id' => $users->random()->id,
                        'parent_id' => $topReply->id,
                        'depth' => 1,
                        'body' => [
                            ['type' => 'paragraph', 'children' => [['text' => fake()->paragraph()]]],
                        ],
                    ]);
                    $replyCount++;

                    // 30% chance of a depth-2 reply
                    if (fake()->boolean(30)) {
                        Reply::create([
                            'discussion_id' => $discussion->id,
                            'user_id' => $users->random()->id,
                            'parent_id' => $childReply->id,
                            'depth' => 2,
                            'body' => [
                                ['type' => 'paragraph', 'children' => [['text' => fake()->sentence()]]],
                            ],
                        ]);
                        $replyCount++;
                    }
                }
            }

            // Update discussion counters (observer handles this on create,
            // but we reset here to be accurate after bulk creation)
            $discussion->update([
                'reply_count' => $discussion->replies()->count(),
                'last_reply_at' => $discussion->replies()->latest()->first()?->created_at,
            ]);
        }
    }
}
