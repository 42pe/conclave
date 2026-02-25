<?php

use App\Models\Discussion;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\NewReplyNotification;

test('fetch notifications returns JSON with expected structure', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => User::factory()->create()->id,
        'depth' => 0,
    ]);

    $user->notify(new NewReplyNotification($reply, $discussion));

    $response = $this
        ->actingAs($user)
        ->getJson(route('notifications.index'));

    $response
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonStructure([
            '*' => ['id', 'type', 'data', 'read_at', 'created_at'],
        ]);
});

test('mark single notification as read', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => User::factory()->create()->id,
        'depth' => 0,
    ]);

    $user->notify(new NewReplyNotification($reply, $discussion));

    $notification = $user->notifications()->first();
    expect($notification->read_at)->toBeNull();

    $this
        ->actingAs($user)
        ->postJson(route('notifications.markAsRead', $notification->id))
        ->assertOk();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('mark all notifications as read', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    // Create two notifications
    for ($i = 0; $i < 2; $i++) {
        $reply = Reply::factory()->create([
            'discussion_id' => $discussion->id,
            'user_id' => User::factory()->create()->id,
            'depth' => 0,
        ]);
        $user->notify(new NewReplyNotification($reply, $discussion));
    }

    expect($user->unreadNotifications()->count())->toBe(2);

    $this
        ->actingAs($user)
        ->postJson(route('notifications.markAllAsRead'))
        ->assertOk();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

test('user can only see their own notifications', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user1->id,
    ]);
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user2->id,
        'depth' => 0,
    ]);

    $user1->notify(new NewReplyNotification($reply, $discussion));

    $this
        ->actingAs($user2)
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonCount(0);
});

test('auth required for notification endpoints', function () {
    $this->getJson(route('notifications.index'))->assertUnauthorized();
    $this->postJson(route('notifications.markAsRead', 'fake-id'))->assertUnauthorized();
    $this->postJson(route('notifications.markAllAsRead'))->assertUnauthorized();
});

test('user cannot mark another users notification as read', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user1->id,
    ]);
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user2->id,
        'depth' => 0,
    ]);

    $user1->notify(new NewReplyNotification($reply, $discussion));
    $notification = $user1->notifications()->first();

    $this
        ->actingAs($user2)
        ->postJson(route('notifications.markAsRead', $notification->id))
        ->assertNotFound();
});
