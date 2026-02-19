<?php

use App\Enums\TopicVisibility;
use App\Models\Discussion;
use App\Models\Location;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function validReplyBody(): array
{
    return [
        ['type' => 'paragraph', 'children' => [['text' => 'This is a reply.']]],
    ];
}

function createDiscussionWithTopic(): array
{
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Public]);
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    return [$topic, $discussion];
}

// === Store ===

it('allows an authenticated user to post a reply', function () {
    $user = User::factory()->create();
    [, $discussion] = createDiscussionWithTopic();

    $response = $this->actingAs($user)->post('/replies', [
        'discussion_id' => $discussion->id,
        'body' => validReplyBody(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('replies', [
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
        'parent_id' => null,
        'depth' => 0,
    ]);
});

it('rejects unauthenticated reply creation', function () {
    [, $discussion] = createDiscussionWithTopic();

    $response = $this->post('/replies', [
        'discussion_id' => $discussion->id,
        'body' => validReplyBody(),
    ]);

    $response->assertRedirect('/login');
});

it('requires a valid discussion_id', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/replies', [
        'discussion_id' => 99999,
        'body' => validReplyBody(),
    ]);

    $response->assertSessionHasErrors('discussion_id');
});

it('requires a body', function () {
    $user = User::factory()->create();
    [, $discussion] = createDiscussionWithTopic();

    $response = $this->actingAs($user)->post('/replies', [
        'discussion_id' => $discussion->id,
    ]);

    $response->assertSessionHasErrors('body');
});

it('validates body is a valid slate document', function () {
    $user = User::factory()->create();
    [, $discussion] = createDiscussionWithTopic();

    $response = $this->actingAs($user)->post('/replies', [
        'discussion_id' => $discussion->id,
        'body' => [['invalid' => 'structure']],
    ]);

    $response->assertSessionHasErrors('body');
});

// === Nested Replies ===

it('allows replying to a reply (depth 1)', function () {
    $user = User::factory()->create();
    [, $discussion] = createDiscussionWithTopic();
    $parentReply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'depth' => 0,
    ]);

    $response = $this->actingAs($user)->post('/replies', [
        'discussion_id' => $discussion->id,
        'parent_id' => $parentReply->id,
        'body' => validReplyBody(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('replies', [
        'discussion_id' => $discussion->id,
        'parent_id' => $parentReply->id,
        'depth' => 1,
    ]);
});

it('allows replying to a depth-1 reply (depth 2)', function () {
    $user = User::factory()->create();
    [, $discussion] = createDiscussionWithTopic();
    $parentReply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'depth' => 1,
    ]);

    $response = $this->actingAs($user)->post('/replies', [
        'discussion_id' => $discussion->id,
        'parent_id' => $parentReply->id,
        'body' => validReplyBody(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('replies', [
        'discussion_id' => $discussion->id,
        'parent_id' => $parentReply->id,
        'depth' => 2,
    ]);
});

it('rejects replies exceeding max depth', function () {
    $user = User::factory()->create();
    [, $discussion] = createDiscussionWithTopic();
    $parentReply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'depth' => 2,
    ]);

    $response = $this->actingAs($user)->post('/replies', [
        'discussion_id' => $discussion->id,
        'parent_id' => $parentReply->id,
        'body' => validReplyBody(),
    ]);

    $response->assertStatus(422);
});

// === Locked Discussion ===

it('prevents non-admin from replying to a locked discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Public]);
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'is_locked' => true,
    ]);

    $response = $this->actingAs($user)->post('/replies', [
        'discussion_id' => $discussion->id,
        'body' => validReplyBody(),
    ]);

    $response->assertForbidden();
});

it('allows admin to reply to a locked discussion', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Public]);
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'is_locked' => true,
    ]);

    $response = $this->actingAs($admin)->post('/replies', [
        'discussion_id' => $discussion->id,
        'body' => validReplyBody(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('replies', [
        'discussion_id' => $discussion->id,
        'user_id' => $admin->id,
    ]);
});

// === Suspended User ===

it('prevents suspended user from replying', function () {
    $user = User::factory()->suspended()->create();
    [, $discussion] = createDiscussionWithTopic();

    $response = $this->actingAs($user)->post('/replies', [
        'discussion_id' => $discussion->id,
        'body' => validReplyBody(),
    ]);

    $response->assertForbidden();
});

// === Update ===

it('allows the reply owner to update their reply', function () {
    $user = User::factory()->create();
    [, $discussion] = createDiscussionWithTopic();
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
    ]);

    $newBody = [
        ['type' => 'paragraph', 'children' => [['text' => 'Updated reply.']]],
    ];

    $response = $this->actingAs($user)->patch("/replies/{$reply->id}", [
        'body' => $newBody,
    ]);

    $response->assertRedirect();
    $reply->refresh();
    expect($reply->body[0]['children'][0]['text'])->toBe('Updated reply.');
});

it('prevents other users from updating a reply', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    [, $discussion] = createDiscussionWithTopic();
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $owner->id,
    ]);

    $response = $this->actingAs($otherUser)->patch("/replies/{$reply->id}", [
        'body' => validReplyBody(),
    ]);

    $response->assertForbidden();
});

it('allows admin to update any reply', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    [, $discussion] = createDiscussionWithTopic();
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $owner->id,
    ]);

    $newBody = [
        ['type' => 'paragraph', 'children' => [['text' => 'Admin edit.']]],
    ];

    $response = $this->actingAs($admin)->patch("/replies/{$reply->id}", [
        'body' => $newBody,
    ]);

    $response->assertRedirect();
    $reply->refresh();
    expect($reply->body[0]['children'][0]['text'])->toBe('Admin edit.');
});

// === Delete ===

it('allows the reply owner to delete their reply', function () {
    $user = User::factory()->create();
    [, $discussion] = createDiscussionWithTopic();
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->delete("/replies/{$reply->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('replies', ['id' => $reply->id]);
});

it('prevents other users from deleting a reply', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    [, $discussion] = createDiscussionWithTopic();
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $owner->id,
    ]);

    $response = $this->actingAs($otherUser)->delete("/replies/{$reply->id}");

    $response->assertForbidden();
});

it('allows admin to delete any reply', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    [, $discussion] = createDiscussionWithTopic();
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $owner->id,
    ]);

    $response = $this->actingAs($admin)->delete("/replies/{$reply->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('replies', ['id' => $reply->id]);
});

// === Observer — reply_count and last_reply_at ===

it('increments discussion reply_count when a reply is created', function () {
    [, $discussion] = createDiscussionWithTopic();
    $discussion->refresh();
    expect($discussion->reply_count)->toBe(0);

    Reply::factory()->create(['discussion_id' => $discussion->id]);

    $discussion->refresh();
    expect($discussion->reply_count)->toBe(1);
});

it('decrements discussion reply_count when a reply is deleted', function () {
    [, $discussion] = createDiscussionWithTopic();
    $reply = Reply::factory()->create(['discussion_id' => $discussion->id]);

    $discussion->refresh();
    expect($discussion->reply_count)->toBe(1);

    $reply->delete();

    $discussion->refresh();
    expect($discussion->reply_count)->toBe(0);
});

it('updates discussion last_reply_at when a reply is created', function () {
    [, $discussion] = createDiscussionWithTopic();
    expect($discussion->last_reply_at)->toBeNull();

    $reply = Reply::factory()->create(['discussion_id' => $discussion->id]);

    $discussion->refresh();
    expect($discussion->last_reply_at)->not->toBeNull();
});

// === Discussion show includes replies ===

it('loads replies on discussion show page', function () {
    $user = User::factory()->create();
    [$topic, $discussion] = createDiscussionWithTopic();
    Reply::factory()->count(3)->create(['discussion_id' => $discussion->id]);

    $response = $this->actingAs($user)->get(
        "/topics/{$topic->slug}/discussions/{$discussion->slug}"
    );

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('discussions/show')
        ->has('replies', 3)
        ->has('canReply')
    );
});

it('passes canReply as false for guests', function () {
    [$topic, $discussion] = createDiscussionWithTopic();

    $response = $this->get(
        "/topics/{$topic->slug}/discussions/{$discussion->slug}"
    );

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('canReply', false)
    );
});
