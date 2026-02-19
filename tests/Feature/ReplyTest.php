<?php

use App\Models\Discussion;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;

// --- Helper for valid Slate body ---

function validReplyBody(): array
{
    return [
        ['type' => 'paragraph', 'children' => [['text' => 'This is a reply.']]],
    ];
}

// --- CRUD: Create replies ---

test('authenticated user can create a top-level reply', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => validReplyBody(),
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $reply = Reply::where('discussion_id', $discussion->id)->first();
    expect($reply)->not->toBeNull();
    expect($reply->user_id)->toBe($user->id);
    expect($reply->parent_id)->toBeNull();
    expect($reply->depth)->toBe(0);
    expect($reply->body)->toBeArray();
});

test('authenticated user can create a nested reply at depth 1', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $parentReply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'depth' => 0,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => validReplyBody(),
            'parent_id' => $parentReply->id,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $reply = Reply::where('parent_id', $parentReply->id)->first();
    expect($reply)->not->toBeNull();
    expect($reply->depth)->toBe(1);
});

test('authenticated user can create a nested reply at depth 2', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $depth0 = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'depth' => 0,
    ]);
    $depth1 = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'parent_id' => $depth0->id,
        'depth' => 1,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => validReplyBody(),
            'parent_id' => $depth1->id,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $reply = Reply::where('parent_id', $depth1->id)->first();
    expect($reply)->not->toBeNull();
    expect($reply->depth)->toBe(2);
});

// --- Depth validation ---

test('creating reply rejects depth beyond 2', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $depth2Reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'depth' => 2,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => validReplyBody(),
            'parent_id' => $depth2Reply->id,
        ]);

    $response->assertSessionHasErrors('parent_id');
});

test('creating reply rejects parent from a different discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion1 = Discussion::factory()->create(['topic_id' => $topic->id]);
    $discussion2 = Discussion::factory()->create(['topic_id' => $topic->id]);
    $otherReply = Reply::factory()->create([
        'discussion_id' => $discussion2->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion1), [
            'body' => validReplyBody(),
            'parent_id' => $otherReply->id,
        ]);

    $response->assertSessionHasErrors('parent_id');
});

// --- Locked discussions ---

test('reply is rejected on a locked discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->locked()->create(['topic_id' => $topic->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => validReplyBody(),
        ]);

    $response->assertForbidden();
});

// --- CRUD: Update replies ---

test('reply owner can update their reply', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
    ]);

    $newBody = [['type' => 'paragraph', 'children' => [['text' => 'Updated reply.']]]];

    $response = $this
        ->actingAs($user)
        ->patch(route('replies.update', $reply), [
            'body' => $newBody,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $reply->refresh();
    expect($reply->body)->toEqual($newBody);
});

test('admin can update any reply', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
    ]);

    $newBody = [['type' => 'paragraph', 'children' => [['text' => 'Admin updated.']]]];

    $response = $this
        ->actingAs($admin)
        ->patch(route('replies.update', $reply), [
            'body' => $newBody,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $reply->refresh();
    expect($reply->body)->toEqual($newBody);
});

test('moderator can update any reply', function () {
    $moderator = User::factory()->moderator()->create();
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
    ]);

    $newBody = [['type' => 'paragraph', 'children' => [['text' => 'Mod updated.']]]];

    $response = $this
        ->actingAs($moderator)
        ->patch(route('replies.update', $reply), [
            'body' => $newBody,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $reply->refresh();
    expect($reply->body)->toEqual($newBody);
});

// --- CRUD: Delete replies ---

test('reply owner can delete their reply', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->delete(route('replies.destroy', $reply));

    $response->assertRedirect();
    expect(Reply::find($reply->id))->toBeNull();
});

test('admin can delete any reply', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
    ]);

    $response = $this
        ->actingAs($admin)
        ->delete(route('replies.destroy', $reply));

    $response->assertRedirect();
    expect(Reply::find($reply->id))->toBeNull();
});

test('moderator can delete any reply', function () {
    $moderator = User::factory()->moderator()->create();
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
    ]);

    $response = $this
        ->actingAs($moderator)
        ->delete(route('replies.destroy', $reply));

    $response->assertRedirect();
    expect(Reply::find($reply->id))->toBeNull();
});

// --- Authorization: 403 for unauthorized users ---

test('non-owner regular user gets 403 when updating reply', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $owner->id,
    ]);

    $response = $this
        ->actingAs($other)
        ->patch(route('replies.update', $reply), [
            'body' => validReplyBody(),
        ]);

    $response->assertForbidden();
});

test('non-owner regular user gets 403 when deleting reply', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $reply = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $owner->id,
    ]);

    $response = $this
        ->actingAs($other)
        ->delete(route('replies.destroy', $reply));

    $response->assertForbidden();
});

// --- Unauthenticated users ---

test('unauthenticated user is redirected to login when creating reply', function () {
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this->post(route('discussions.replies.store', $discussion), [
        'body' => validReplyBody(),
    ]);

    $response->assertRedirect(route('login'));
});

test('unauthenticated user is redirected to login when updating reply', function () {
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $reply = Reply::factory()->create(['discussion_id' => $discussion->id]);

    $response = $this->patch(route('replies.update', $reply), [
        'body' => validReplyBody(),
    ]);

    $response->assertRedirect(route('login'));
});

test('unauthenticated user is redirected to login when deleting reply', function () {
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $reply = Reply::factory()->create(['discussion_id' => $discussion->id]);

    $response = $this->delete(route('replies.destroy', $reply));

    $response->assertRedirect(route('login'));
});

// --- Validation ---

test('creating reply requires a body', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => null,
        ]);

    $response->assertSessionHasErrors('body');
});

test('creating reply rejects invalid slate body', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => [['type' => 'invalid-type', 'children' => [['text' => 'Test']]]],
        ]);

    $response->assertSessionHasErrors('body');
});

test('creating reply rejects non-existent parent_id', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => validReplyBody(),
            'parent_id' => 99999,
        ]);

    $response->assertSessionHasErrors('parent_id');
});

test('updating reply requires a body', function () {
    $user = User::factory()->create();
    $reply = Reply::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->patch(route('replies.update', $reply), [
            'body' => null,
        ]);

    $response->assertSessionHasErrors('body');
});

test('updating reply rejects invalid slate body', function () {
    $user = User::factory()->create();
    $reply = Reply::factory()->create(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->patch(route('replies.update', $reply), [
            'body' => [['type' => 'invalid-type', 'children' => [['text' => 'Test']]]],
        ]);

    $response->assertSessionHasErrors('body');
});

// --- Observer: reply_count and last_reply_at ---

test('creating a reply increments discussion reply_count', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'reply_count' => 0,
    ]);

    $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => validReplyBody(),
        ]);

    $discussion->refresh();
    expect($discussion->reply_count)->toBe(1);
});

test('creating a reply updates discussion last_reply_at', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'last_reply_at' => null,
    ]);

    $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => validReplyBody(),
        ]);

    $discussion->refresh();
    expect($discussion->last_reply_at)->not->toBeNull();
});

test('deleting a reply decrements discussion reply_count', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    // Create reply via route so observer sets reply_count to 1
    $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => validReplyBody(),
        ]);

    $discussion->refresh();
    expect($discussion->reply_count)->toBe(1);

    $reply = Reply::where('discussion_id', $discussion->id)->first();

    $this
        ->actingAs($user)
        ->delete(route('replies.destroy', $reply));

    $discussion->refresh();
    expect($discussion->reply_count)->toBe(0);
});

// --- Discussion show includes replies ---

test('discussion show page includes replies', function () {
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    Reply::factory()->create(['discussion_id' => $discussion->id]);

    $response = $this->get(route('topics.discussions.show', [$topic, $discussion]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('discussions/show', false)
        ->has('replies', 1)
    );
});

test('discussion show page includes nested replies', function () {
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $parent = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'depth' => 0,
    ]);
    Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'parent_id' => $parent->id,
        'depth' => 1,
    ]);

    $response = $this->get(route('topics.discussions.show', [$topic, $discussion]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('discussions/show', false)
        ->has('replies', 1)
        ->has('replies.0.children', 1)
    );
});

test('discussion show includes reply permission', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('topics.discussions.show', [$topic, $discussion]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('discussions/show', false)
        ->where('can.reply', true)
    );
});

test('discussion show denies reply permission for locked discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->locked()->create(['topic_id' => $topic->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('topics.discussions.show', [$topic, $discussion]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('discussions/show', false)
        ->where('can.reply', false)
    );
});

// --- Model relationships ---

test('reply belongs to a discussion', function () {
    $discussion = Discussion::factory()->create();
    $reply = Reply::factory()->create(['discussion_id' => $discussion->id]);

    expect($reply->discussion->id)->toBe($discussion->id);
});

test('reply belongs to a user', function () {
    $user = User::factory()->create();
    $reply = Reply::factory()->create(['user_id' => $user->id]);

    expect($reply->user->id)->toBe($user->id);
});

test('reply can have a parent reply', function () {
    $discussion = Discussion::factory()->create();
    $parent = Reply::factory()->create(['discussion_id' => $discussion->id]);
    $child = Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'parent_id' => $parent->id,
        'depth' => 1,
    ]);

    expect($child->parent->id)->toBe($parent->id);
});

test('reply can have child replies', function () {
    $discussion = Discussion::factory()->create();
    $parent = Reply::factory()->create(['discussion_id' => $discussion->id]);
    Reply::factory()->count(3)->create([
        'discussion_id' => $discussion->id,
        'parent_id' => $parent->id,
        'depth' => 1,
    ]);

    expect($parent->children)->toHaveCount(3);
});

test('discussion has many replies', function () {
    $discussion = Discussion::factory()->create();
    Reply::factory()->count(3)->create(['discussion_id' => $discussion->id]);

    expect($discussion->replies)->toHaveCount(3);
});
