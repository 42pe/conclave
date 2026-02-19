<?php

use App\Models\Conversation;
use App\Models\Discussion;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Notifications\NewReplyNotification;
use App\Services\PostHogService;
use Illuminate\Support\Facades\Notification;

// --- Helper ---

function notifSlateBody(): array
{
    return [
        ['type' => 'paragraph', 'children' => [['text' => 'Notification test body']]],
    ];
}

function notifConversation(User $user1, User $user2): Conversation
{
    $conversation = Conversation::factory()->create();
    $conversation->participants()->createMany([
        ['user_id' => $user1->id, 'last_read_at' => now()],
        ['user_id' => $user2->id],
    ]);

    return $conversation;
}

// --- Reply notifications ---

test('NewReplyNotification is sent to discussion author when reply is created', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $author = User::factory()->create();
    $replier = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
    ]);

    $this
        ->actingAs($replier)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => notifSlateBody(),
        ]);

    Notification::assertSentTo($author, NewReplyNotification::class);
});

test('NewReplyNotification is sent to parent reply author for nested replies', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $discussionAuthor = User::factory()->create();
    $parentReplyAuthor = User::factory()->create();
    $replier = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $discussionAuthor->id,
    ]);
    $parentReply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $parentReplyAuthor->id,
        'depth' => 0,
    ]);

    $this
        ->actingAs($replier)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => notifSlateBody(),
            'parent_id' => $parentReply->id,
        ]);

    Notification::assertSentTo($parentReplyAuthor, NewReplyNotification::class);
});

test('no self-notification when replying to own discussion', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => notifSlateBody(),
        ]);

    Notification::assertNotSentTo($user, NewReplyNotification::class);
});

test('NewReplyNotification not sent when user has notify_replies disabled', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $author = User::factory()->create(['notify_replies' => false]);
    $replier = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
    ]);

    $this
        ->actingAs($replier)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => notifSlateBody(),
        ]);

    // When via() returns [], Notification::fake() doesn't record the notification
    Notification::assertNotSentTo($author, NewReplyNotification::class);
});

// --- Message notifications ---

test('NewMessageNotification is sent to other participants when message is sent', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = notifConversation($sender, $recipient);

    $this
        ->actingAs($sender)
        ->post(route('messages.store', $conversation), [
            'body' => notifSlateBody(),
        ]);

    Notification::assertSentTo($recipient, NewMessageNotification::class);
});

test('no self-notification for messages', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $sender = User::factory()->create();
    $other = User::factory()->create();
    $conversation = notifConversation($sender, $other);

    $this
        ->actingAs($sender)
        ->post(route('messages.store', $conversation), [
            'body' => notifSlateBody(),
        ]);

    Notification::assertNotSentTo($sender, NewMessageNotification::class);
});

test('NewMessageNotification not sent when user has notify_messages disabled', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $sender = User::factory()->create();
    $recipient = User::factory()->create(['notify_messages' => false]);
    $conversation = notifConversation($sender, $recipient);

    $this
        ->actingAs($sender)
        ->post(route('messages.store', $conversation), [
            'body' => notifSlateBody(),
        ]);

    // When via() returns [], Notification::fake() doesn't record the notification
    Notification::assertNotSentTo($recipient, NewMessageNotification::class);
});
