<?php

use App\Models\Discussion;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;
use App\Services\PostHogService;

// --- Discussion likes ---

test('user can like a discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this->actingAs($user)
        ->postJson(route('discussions.like', $discussion));

    $response->assertOk()->assertJson(['liked' => true, 'likes_count' => 1]);
    expect($discussion->likes()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('user can unlike a discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $discussion->likes()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson(route('discussions.like', $discussion));

    $response->assertOk()->assertJson(['liked' => false, 'likes_count' => 0]);
});

test('like count is correct with multiple users', function () {
    $users = User::factory()->count(3)->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    foreach ($users as $u) {
        $discussion->likes()->create(['user_id' => $u->id]);
    }

    $response = $this->actingAs($users[0])
        ->postJson(route('discussions.like', $discussion));

    $response->assertJson(['liked' => false, 'likes_count' => 2]);
});

// --- Reply likes ---

test('user can like a reply', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $reply = Reply::factory()->create(['discussion_id' => $discussion->id, 'depth' => 0]);

    $response = $this->actingAs($user)
        ->postJson(route('replies.like', $reply));

    $response->assertOk()->assertJson(['liked' => true, 'likes_count' => 1]);
});

test('user can unlike a reply', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $reply = Reply::factory()->create(['discussion_id' => $discussion->id, 'depth' => 0]);
    $reply->likes()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson(route('replies.like', $reply));

    $response->assertOk()->assertJson(['liked' => false, 'likes_count' => 0]);
});

// --- Auth ---

test('unauthenticated user cannot like', function () {
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $this->postJson(route('discussions.like', $discussion))->assertUnauthorized();
});

test('suspended user cannot like', function () {
    $user = User::factory()->create(['is_suspended' => true]);
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $this->actingAs($user)
        ->postJson(route('discussions.like', $discussion))
        ->assertForbidden();
});

// --- Show page data ---

test('discussion show page includes like data', function () {
    $this->mock(PostHogService::class)->shouldReceive('capture');
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $discussion->likes()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->get(route('topics.discussions.show', [$topic, $discussion]));

    $response->assertInertia(fn ($page) => $page
        ->component('discussions/show')
        ->where('discussion.likes_count', 1)
        ->where('discussion.user_has_liked', true)
    );
});

test('discussion show page includes reply like data', function () {
    $this->mock(PostHogService::class)->shouldReceive('capture');
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $reply = Reply::factory()->create(['discussion_id' => $discussion->id, 'depth' => 0]);
    $reply->likes()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->get(route('topics.discussions.show', [$topic, $discussion]));

    $response->assertInertia(fn ($page) => $page
        ->component('discussions/show')
        ->where('replies.0.likes_count', 1)
        ->where('replies.0.user_has_liked', true)
    );
});
