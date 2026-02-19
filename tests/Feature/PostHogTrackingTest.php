<?php

use App\Models\Discussion;
use App\Models\Topic;
use App\Models\User;
use App\Services\PostHogService;

// --- Helper ---

function trackingSlateBody(): array
{
    return [
        ['type' => 'paragraph', 'children' => [['text' => 'Test content']]],
    ];
}

function trackingConversation(User $user1, User $user2): \App\Models\Conversation
{
    $conversation = \App\Models\Conversation::factory()->create();
    $conversation->participants()->createMany([
        ['user_id' => $user1->id, 'last_read_at' => now()],
        ['user_id' => $user2->id],
    ]);

    return $conversation;
}

// --- Discussion tracking ---

test('PostHog captures discussion_created when creating a discussion', function () {
    $mock = $this->mock(PostHogService::class);
    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(fn ($distinctId, $event) => $event === 'discussion_created');

    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'Test Discussion',
            'body' => trackingSlateBody(),
        ]);
});

test('PostHog captures discussion_viewed when viewing a discussion', function () {
    $mock = $this->mock(PostHogService::class);
    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(fn ($distinctId, $event) => $event === 'discussion_viewed');

    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $this
        ->actingAs($user)
        ->get(route('topics.discussions.show', [$topic, $discussion]));
});

// --- Reply tracking ---

test('PostHog captures reply_created when creating a reply', function () {
    $mock = $this->mock(PostHogService::class);
    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(fn ($distinctId, $event) => $event === 'reply_created');

    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => trackingSlateBody(),
        ]);
});

// --- Message tracking ---

test('PostHog captures message_sent when sending a message', function () {
    $mock = $this->mock(PostHogService::class);
    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(fn ($distinctId, $event) => $event === 'message_sent');

    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = trackingConversation($user, $other);

    $this
        ->actingAs($user)
        ->post(route('messages.store', $conversation), [
            'body' => trackingSlateBody(),
        ]);
});

// --- Moderation tracking ---

test('PostHog captures user_suspended on suspend action', function () {
    $mock = $this->mock(PostHogService::class);
    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(fn ($distinctId, $event) => $event === 'user_suspended');

    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this
        ->actingAs($admin)
        ->post(route('admin.users.suspend', $target));
});

test('PostHog captures user_banned on ban action', function () {
    $mock = $this->mock(PostHogService::class);
    $mock->shouldReceive('capture')
        ->once()
        ->withArgs(fn ($distinctId, $event) => $event === 'user_banned');

    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this
        ->actingAs($admin)
        ->post(route('admin.users.ban', $target), [
            'reason' => 'Violating terms of service',
        ]);
});

// --- Graceful handling ---

test('PostHog handles missing API key gracefully', function () {
    // Bind a PostHogService with empty API key (disabled)
    $this->app->singleton(PostHogService::class, fn () => new PostHogService(
        apiKey: '',
        host: 'https://us.i.posthog.com',
    ));

    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    // This should not throw any errors even with no API key
    $response = $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'Test Discussion',
            'body' => trackingSlateBody(),
        ]);

    $response->assertRedirect();
});
