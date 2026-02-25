<?php

use App\Models\Discussion;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\MentionNotification;
use App\Services\MentionService;
use App\Services\PostHogService;
use Illuminate\Support\Facades\Notification;

// --- Helper ---

function mentionBody(int $userId, string $username): array
{
    return [
        [
            'type' => 'paragraph',
            'children' => [
                ['text' => 'Hey '],
                [
                    'type' => 'mention',
                    'userId' => $userId,
                    'username' => $username,
                    'children' => [['text' => '']],
                ],
                ['text' => ' check this out'],
            ],
        ],
    ];
}

// --- Discussion mentions ---

test('MentionNotification sent when discussion with mention is created', function () {
    Notification::fake();

    $author = User::factory()->create();
    $mentioned = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
        'body' => mentionBody($mentioned->id, $mentioned->username),
    ]);

    $service = new MentionService;
    $service->notifyMentionedUsers($discussion->body, $author, $discussion);

    Notification::assertSentTo($mentioned, MentionNotification::class);
});

// --- Reply mentions ---

test('MentionNotification sent when reply with mention is created', function () {
    Notification::fake();

    $author = User::factory()->create();
    $mentioned = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
    ]);
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $author->id,
        'body' => mentionBody($mentioned->id, $mentioned->username),
        'depth' => 0,
    ]);

    $service = new MentionService;
    $service->notifyMentionedUsers($reply->body, $author, $discussion, $reply);

    Notification::assertSentTo($mentioned, MentionNotification::class);
});

// --- Self-mention ---

test('no self-mention notification', function () {
    Notification::fake();

    $author = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
        'body' => mentionBody($author->id, $author->username),
    ]);

    $service = new MentionService;
    $service->notifyMentionedUsers($discussion->body, $author, $discussion);

    Notification::assertNotSentTo($author, MentionNotification::class);
});

// --- Disabled preference ---

test('MentionNotification email not sent when user has notify_mentions disabled', function () {
    Notification::fake();

    $author = User::factory()->create();
    $mentioned = User::factory()->create(['notify_mentions' => false]);
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
        'body' => mentionBody($mentioned->id, $mentioned->username),
    ]);

    $service = new MentionService;
    $service->notifyMentionedUsers($discussion->body, $author, $discussion);

    // Database notification is still sent, but email channel is excluded
    Notification::assertSentTo(
        $mentioned,
        MentionNotification::class,
        fn ($notification, $channels) => in_array('database', $channels) && ! in_array('mail', $channels),
    );
});

// --- Integration: controller calls MentionService ---

test('creating a discussion triggers mention notifications via controller', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $mentionService = Mockery::mock(MentionService::class);
    $mentionService->shouldReceive('notifyMentionedUsers')->once();
    $this->instance(MentionService::class, $mentionService);

    $author = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    $this
        ->actingAs($author)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'Discussion With Mention',
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Hello']]]],
        ]);
});

test('creating a reply triggers mention notifications via controller', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $mentionService = Mockery::mock(MentionService::class);
    $mentionService->shouldReceive('notifyMentionedUsers')->once();
    $this->instance(MentionService::class, $mentionService);

    $author = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
    ]);

    $this
        ->actingAs($author)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Hello']]]],
        ]);
});
