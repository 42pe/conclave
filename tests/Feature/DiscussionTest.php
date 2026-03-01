<?php

use App\Models\Discussion;
use App\Models\Location;
use App\Models\Topic;
use App\Models\User;

// --- Helper for valid Slate body ---

function validSlateBody(): array
{
    return [
        ['type' => 'paragraph', 'children' => [['text' => 'Test content for discussion.']]],
    ];
}

// --- CRUD: Create discussions ---

test('authenticated user can view the create discussion page for a public topic', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('topics.discussions.create', $topic));

    $response->assertOk();
});

test('authenticated user can create a discussion in a public topic', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'My First Discussion',
            'body' => validSlateBody(),
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $discussion = Discussion::where('title', 'My First Discussion')->first();
    expect($discussion)->not->toBeNull();
    expect($discussion->topic_id)->toBe($topic->id);
    expect($discussion->user_id)->toBe($user->id);
    expect($discussion->body)->toBeArray();
    expect($discussion->is_pinned)->toBeFalse();
    expect($discussion->is_locked)->toBeFalse();
});

test('authenticated user can create a discussion with a location', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $location = Location::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'Discussion With Location',
            'body' => validSlateBody(),
            'location_id' => $location->id,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $discussion = Discussion::where('title', 'Discussion With Location')->first();
    expect($discussion->location_id)->toBe($location->id);
});

test('authenticated user can create a discussion in a private topic', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->private()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'Private Topic Discussion',
            'body' => validSlateBody(),
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    expect(Discussion::where('title', 'Private Topic Discussion')->exists())->toBeTrue();
});

// --- CRUD: Read discussions ---

test('anyone can view discussions in a public topic', function () {
    $topic = Topic::factory()->public()->create();
    Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this->get(route('topics.show', $topic));

    $response->assertOk();
});

test('anyone can view a single discussion in a public topic', function () {
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this->get(route('topics.discussions.show', [$topic, $discussion]));

    $response->assertOk();
});

test('authenticated user can view discussions in a private topic', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->private()->create();
    Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('topics.show', $topic));

    $response->assertOk();
});

test('admin can view discussions in a restricted topic', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->restricted()->create();
    Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this
        ->actingAs($admin)
        ->get(route('topics.show', $topic));

    $response->assertOk();
});

test('moderator can view discussions in a restricted topic', function () {
    $moderator = User::factory()->moderator()->create();
    $topic = Topic::factory()->restricted()->create();

    $response = $this
        ->actingAs($moderator)
        ->get(route('topics.show', $topic));

    $response->assertOk();
});

// --- CRUD: Update discussions ---

test('discussion owner can view edit page', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('topics.discussions.edit', [$topic, $discussion]));

    $response->assertOk();
});

test('discussion owner can update their discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    $newBody = [['type' => 'paragraph', 'children' => [['text' => 'Updated content.']]]];

    $response = $this
        ->actingAs($user)
        ->patch(route('topics.discussions.update', [$topic, $discussion]), [
            'title' => 'Updated Title',
            'body' => $newBody,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $discussion->refresh();
    expect($discussion->title)->toBe('Updated Title');
    expect($discussion->body)->toEqual($newBody);
});

test('admin can update any discussion', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patch(route('topics.discussions.update', [$topic, $discussion]), [
            'title' => 'Admin Updated',
            'body' => validSlateBody(),
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $discussion->refresh();
    expect($discussion->title)->toBe('Admin Updated');
});

test('moderator can update any discussion', function () {
    $moderator = User::factory()->moderator()->create();
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    $response = $this
        ->actingAs($moderator)
        ->patch(route('topics.discussions.update', [$topic, $discussion]), [
            'title' => 'Mod Updated',
            'body' => validSlateBody(),
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $discussion->refresh();
    expect($discussion->title)->toBe('Mod Updated');
});

// --- CRUD: Delete discussions ---

test('discussion owner can delete their discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->delete(route('topics.discussions.destroy', [$topic, $discussion]));

    $response->assertRedirect();
    expect(Discussion::find($discussion->id))->toBeNull();
});

test('admin can delete any discussion', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    $response = $this
        ->actingAs($admin)
        ->delete(route('topics.discussions.destroy', [$topic, $discussion]));

    $response->assertRedirect();
    expect(Discussion::find($discussion->id))->toBeNull();
});

test('moderator can delete any discussion', function () {
    $moderator = User::factory()->moderator()->create();
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    $response = $this
        ->actingAs($moderator)
        ->delete(route('topics.discussions.destroy', [$topic, $discussion]));

    $response->assertRedirect();
    expect(Discussion::find($discussion->id))->toBeNull();
});

// --- Authorization: 403 for unauthorized users ---

test('non-owner regular user gets 403 when editing discussion', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $owner->id,
    ]);

    $response = $this
        ->actingAs($other)
        ->get(route('topics.discussions.edit', [$topic, $discussion]));

    $response->assertForbidden();
});

test('non-owner regular user gets 403 when updating discussion', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $owner->id,
    ]);

    $response = $this
        ->actingAs($other)
        ->patch(route('topics.discussions.update', [$topic, $discussion]), [
            'title' => 'Hacked Title',
            'body' => validSlateBody(),
        ]);

    $response->assertForbidden();
});

test('non-owner regular user gets 403 when deleting discussion', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $owner->id,
    ]);

    $response = $this
        ->actingAs($other)
        ->delete(route('topics.discussions.destroy', [$topic, $discussion]));

    $response->assertForbidden();
});

// --- Authorization: topic visibility enforcement ---

test('unauthenticated user is redirected to login from private topic discussions', function () {
    $topic = Topic::factory()->private()->create();

    $response = $this->get(route('topics.show', $topic));

    $response->assertRedirect(route('login'));
});

test('unauthenticated user is redirected to login from restricted topic discussions', function () {
    $topic = Topic::factory()->restricted()->create();

    $response = $this->get(route('topics.show', $topic));

    $response->assertRedirect(route('login'));
});

test('regular user is forbidden from viewing restricted topic discussions', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->restricted()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('topics.show', $topic));

    $response->assertForbidden();
});

test('regular user cannot create discussion in a restricted topic', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->restricted()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'Restricted Discussion',
            'body' => validSlateBody(),
        ]);

    $response->assertForbidden();
});

test('admin can create discussion in a restricted topic', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->restricted()->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'Admin Restricted Discussion',
            'body' => validSlateBody(),
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    expect(Discussion::where('title', 'Admin Restricted Discussion')->exists())->toBeTrue();
});

test('moderator can create discussion in a restricted topic', function () {
    $moderator = User::factory()->moderator()->create();
    $topic = Topic::factory()->restricted()->create();

    $response = $this
        ->actingAs($moderator)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'Moderator Restricted Discussion',
            'body' => validSlateBody(),
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    expect(Discussion::where('title', 'Moderator Restricted Discussion')->exists())->toBeTrue();
});

// --- Unauthenticated users ---

test('unauthenticated user is redirected to login when creating discussion', function () {
    $topic = Topic::factory()->public()->create();

    $response = $this->post(route('topics.discussions.store', $topic), [
        'title' => 'Unauthenticated Discussion',
        'body' => validSlateBody(),
    ]);

    $response->assertRedirect(route('login'));
});

test('unauthenticated user is redirected to login when editing discussion', function () {
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this->get(route('topics.discussions.edit', [$topic, $discussion]));

    $response->assertRedirect(route('login'));
});

test('unauthenticated user is redirected to login when deleting discussion', function () {
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this->delete(route('topics.discussions.destroy', [$topic, $discussion]));

    $response->assertRedirect(route('login'));
});

// --- Slug generation ---

test('discussion slug is auto-generated from title', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'My First Discussion',
            'body' => validSlateBody(),
        ]);

    $discussion = Discussion::where('title', 'My First Discussion')->first();
    expect($discussion->slug)->toBe('my-first-discussion');
});

test('discussion slug is unique within the same topic', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'Same Title',
            'body' => validSlateBody(),
        ]);

    $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'Same Title',
            'body' => validSlateBody(),
        ]);

    $discussions = Discussion::where('topic_id', $topic->id)->get();
    expect($discussions)->toHaveCount(2);
    expect($discussions->pluck('slug')->unique())->toHaveCount(2);
});

test('same slug can exist in different topics', function () {
    $user = User::factory()->create();
    $topic1 = Topic::factory()->public()->create();
    $topic2 = Topic::factory()->public()->create();

    $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic1), [
            'title' => 'Same Title',
            'body' => validSlateBody(),
        ]);

    $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic2), [
            'title' => 'Same Title',
            'body' => validSlateBody(),
        ]);

    $d1 = Discussion::where('topic_id', $topic1->id)->first();
    $d2 = Discussion::where('topic_id', $topic2->id)->first();

    expect($d1->slug)->toBe('same-title');
    expect($d2->slug)->toBe('same-title');
});

// --- Validation ---

test('creating discussion requires a title', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => '',
            'body' => validSlateBody(),
        ]);

    $response->assertSessionHasErrors('title');
});

test('creating discussion requires a body', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'No Body Discussion',
            'body' => null,
        ]);

    $response->assertSessionHasErrors('body');
});

test('creating discussion rejects invalid slate body', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'Invalid Body Discussion',
            'body' => [['type' => 'invalid-type', 'children' => [['text' => 'Test']]]],
        ]);

    $response->assertSessionHasErrors('body');
});

test('creating discussion rejects title longer than 255 characters', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => str_repeat('a', 256),
            'body' => validSlateBody(),
        ]);

    $response->assertSessionHasErrors('title');
});

test('creating discussion rejects invalid location id', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'Invalid Location',
            'body' => validSlateBody(),
            'location_id' => 99999,
        ]);

    $response->assertSessionHasErrors('location_id');
});

// --- Pinned discussions appear first ---

test('pinned discussions appear before unpinned ones', function () {
    $topic = Topic::factory()->public()->create();
    $unpinned = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'title' => 'Unpinned',
        'created_at' => now()->subDay(),
    ]);
    $pinned = Discussion::factory()->pinned()->create([
        'topic_id' => $topic->id,
        'title' => 'Pinned',
        'created_at' => now()->subDays(2),
    ]);

    $response = $this->get(route('topics.show', $topic));

    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('topics/show', false)
        ->has('discussions.data', 2)
        ->where('discussions.data.0.id', $pinned->id)
        ->where('discussions.data.1.id', $unpinned->id)
    );
});

// --- Location filter ---

test('discussion index shows discussions with location data', function () {
    $topic = Topic::factory()->public()->create();
    $location = Location::factory()->create();
    Discussion::factory()->create([
        'topic_id' => $topic->id,
        'location_id' => $location->id,
    ]);

    $response = $this->get(route('topics.show', $topic));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('topics/show', false)
        ->has('discussions.data', 1)
        ->has('locations')
    );
});
