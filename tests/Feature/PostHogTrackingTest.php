<?php

use App\Models\Conversation;
use App\Models\Discussion;
use App\Models\Message;
use App\Models\Topic;
use App\Models\User;
use App\Services\PostHogService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function mockPostHog(): Mockery\MockInterface
{
    $mock = Mockery::mock(PostHogService::class);
    $mock->shouldReceive('capture')->byDefault();
    $mock->shouldReceive('identify')->byDefault();
    app()->instance(PostHogService::class, $mock);

    return $mock;
}

function validSlateBody(): array
{
    return [
        ['type' => 'paragraph', 'children' => [['text' => 'Test content']]],
    ];
}

// === Discussion Tracking ===

it('tracks discussion_created event', function () {
    $mock = mockPostHog();
    $user = User::factory()->create();
    $topic = Topic::factory()->create();

    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(function ($capturedUser, $event, $properties) use ($user) {
            return $capturedUser->id === $user->id
                && $event === 'discussion_created'
                && isset($properties['discussion_id'])
                && isset($properties['topic_id']);
        });

    $this->actingAs($user)->post('/discussions', [
        'topic_id' => $topic->id,
        'title' => 'Test Discussion',
        'body' => validSlateBody(),
    ]);
});

it('tracks discussion_viewed event', function () {
    $mock = mockPostHog();
    $user = User::factory()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id, 'user_id' => $user->id]);

    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(function ($capturedUser, $event, $properties) use ($user, $discussion) {
            return $capturedUser->id === $user->id
                && $event === 'discussion_viewed'
                && $properties['discussion_id'] === $discussion->id;
        });

    $this->actingAs($user)->get("/topics/{$topic->slug}/discussions/{$discussion->slug}");
});

// === Reply Tracking ===

it('tracks reply_created event', function () {
    $mock = mockPostHog();
    $user = User::factory()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id, 'user_id' => $user->id]);

    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(function ($capturedUser, $event, $properties) use ($user) {
            return $capturedUser->id === $user->id
                && $event === 'reply_created'
                && isset($properties['reply_id'])
                && isset($properties['depth']);
        });

    $this->actingAs($user)->post('/replies', [
        'discussion_id' => $discussion->id,
        'body' => validSlateBody(),
    ]);
});

// === Conversation Tracking ===

it('tracks conversation_started event', function () {
    $mock = mockPostHog();
    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(function ($capturedUser, $event) use ($user) {
            return $capturedUser->id === $user->id
                && $event === 'conversation_started';
        });

    $this->actingAs($user)->post('/conversations', [
        'recipient_id' => $recipient->id,
        'body' => validSlateBody(),
    ]);
});

// === Message Tracking ===

it('tracks message_sent event', function () {
    $mock = mockPostHog();
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = Conversation::create();
    $conversation->participants()->attach([$user->id, $other->id]);

    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(function ($capturedUser, $event) use ($user) {
            return $capturedUser->id === $user->id
                && $event === 'message_sent';
        });

    $this->actingAs($user)->post('/messages', [
        'conversation_id' => $conversation->id,
        'body' => validSlateBody(),
    ]);
});

// === Moderation Tracking ===

it('tracks user_suspended event', function () {
    $mock = mockPostHog();
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(function ($capturedUser, $event, $properties) use ($admin, $target) {
            return $capturedUser->id === $admin->id
                && $event === 'user_suspended'
                && $properties['target_user_id'] === $target->id;
        });

    $this->actingAs($admin)->post("/admin/users/{$target->id}/suspend");
});

it('tracks user_banned event', function () {
    $mock = mockPostHog();
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(function ($capturedUser, $event) use ($admin) {
            return $capturedUser->id === $admin->id
                && $event === 'user_banned';
        });

    $this->actingAs($admin)->post("/admin/users/{$target->id}/ban", [
        'reason' => 'Spamming',
    ]);
});

it('tracks user_deleted event', function () {
    $mock = mockPostHog();
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(function ($capturedUser, $event) use ($admin) {
            return $capturedUser->id === $admin->id
                && $event === 'user_deleted';
        });

    $this->actingAs($admin)->delete("/admin/users/{$target->id}");
});
