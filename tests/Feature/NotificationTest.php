<?php

use App\Models\Conversation;
use App\Models\Discussion;
use App\Models\Message;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Notifications\NewReplyNotification;
use App\Services\PostHogService;
use Illuminate\Support\Facades\Notification;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function slateBody(): array
{
    return [
        ['type' => 'paragraph', 'children' => [['text' => 'Test content']]],
    ];
}

beforeEach(function () {
    // Mock PostHog to prevent actual API calls
    $mock = Mockery::mock(PostHogService::class);
    $mock->shouldReceive('capture')->byDefault();
    $mock->shouldReceive('identify')->byDefault();
    app()->instance(PostHogService::class, $mock);
});

// === Reply Notifications ===

it('sends reply notification to discussion author', function () {
    Notification::fake();

    $author = User::factory()->create();
    $replier = User::factory()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
    ]);

    $this->actingAs($replier)->post('/replies', [
        'discussion_id' => $discussion->id,
        'body' => slateBody(),
    ]);

    Notification::assertSentTo($author, NewReplyNotification::class);
});

it('does not send reply notification to self', function () {
    Notification::fake();

    $author = User::factory()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
    ]);

    $this->actingAs($author)->post('/replies', [
        'discussion_id' => $discussion->id,
        'body' => slateBody(),
    ]);

    Notification::assertNotSentTo($author, NewReplyNotification::class);
});

it('does not send reply notification to deleted user', function () {
    Notification::fake();

    $author = User::factory()->deleted()->create();
    $replier = User::factory()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
    ]);

    $this->actingAs($replier)->post('/replies', [
        'discussion_id' => $discussion->id,
        'body' => slateBody(),
    ]);

    Notification::assertNotSentTo($author, NewReplyNotification::class);
});

it('respects notify_replies preference when disabled', function () {
    Notification::fake();

    $author = User::factory()->create(['notify_replies' => false]);
    $replier = User::factory()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
    ]);

    $this->actingAs($replier)->post('/replies', [
        'discussion_id' => $discussion->id,
        'body' => slateBody(),
    ]);

    // When via() returns [], Laravel doesn't record the notification as sent
    Notification::assertNotSentTo($author, NewReplyNotification::class);
});

// === Message Notifications ===

it('sends message notification when starting conversation', function () {
    Notification::fake();

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $this->actingAs($sender)->post('/conversations', [
        'recipient_id' => $recipient->id,
        'body' => slateBody(),
    ]);

    Notification::assertSentTo($recipient, NewMessageNotification::class);
});

it('sends message notification to other participants on new message', function () {
    Notification::fake();

    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = Conversation::create();
    $conversation->participants()->attach([$sender->id, $recipient->id]);

    $this->actingAs($sender)->post('/messages', [
        'conversation_id' => $conversation->id,
        'body' => slateBody(),
    ]);

    Notification::assertSentTo($recipient, NewMessageNotification::class);
    Notification::assertNotSentTo($sender, NewMessageNotification::class);
});

it('does not send message notification to deleted participant', function () {
    Notification::fake();

    $sender = User::factory()->create();
    $recipient = User::factory()->deleted()->create();
    $conversation = Conversation::create();
    $conversation->participants()->attach([$sender->id, $recipient->id]);

    $this->actingAs($sender)->post('/messages', [
        'conversation_id' => $conversation->id,
        'body' => slateBody(),
    ]);

    Notification::assertNotSentTo($recipient, NewMessageNotification::class);
});

it('respects notify_messages preference when disabled', function () {
    Notification::fake();

    $sender = User::factory()->create();
    $recipient = User::factory()->create(['notify_messages' => false]);

    $this->actingAs($sender)->post('/conversations', [
        'recipient_id' => $recipient->id,
        'body' => slateBody(),
    ]);

    // When via() returns [], Laravel doesn't record the notification as sent
    Notification::assertNotSentTo($recipient, NewMessageNotification::class);
});
