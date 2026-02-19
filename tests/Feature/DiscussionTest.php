<?php

use App\Enums\TopicVisibility;
use App\Models\Discussion;
use App\Models\Location;
use App\Models\Topic;
use App\Models\User;

// --- Authorization ---

test('guest can view discussions in a public topic', function () {
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Public]);

    $this->get(route('topics.show', $topic))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('topics/show')
            ->where('topic.id', $topic->id)
            ->where('auth.user', null)
        );
});

test('guest cannot view discussions in a private topic', function () {
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Private]);

    $this->get(route('topics.show', $topic))
        ->assertForbidden();
});

test('authenticated user can view discussions in a private topic', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Private]);

    $this->actingAs($user)
        ->get(route('topics.show', $topic))
        ->assertSuccessful();
});

test('guest cannot view discussions in a restricted topic', function () {
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Restricted]);

    $this->get(route('topics.show', $topic))
        ->assertForbidden();
});

test('regular user cannot view discussions in a restricted topic', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Restricted]);

    $this->actingAs($user)
        ->get(route('topics.show', $topic))
        ->assertForbidden();
});

test('admin can view discussions in a restricted topic', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Restricted]);

    $this->actingAs($admin)
        ->get(route('topics.show', $topic))
        ->assertSuccessful();
});

test('moderator can view discussions in a restricted topic', function () {
    $mod = User::factory()->moderator()->create();
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Restricted]);

    $this->actingAs($mod)
        ->get(route('topics.show', $topic))
        ->assertSuccessful();
});

test('guest cannot create a discussion', function () {
    $topic = Topic::factory()->create();

    $this->get(route('discussions.create', $topic))
        ->assertRedirect(route('login'));
});

test('guest cannot store a discussion', function () {
    $this->post(route('discussions.store'))
        ->assertRedirect(route('login'));
});

// --- CRUD ---

test('topic show page displays discussions', function () {
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id, 'title' => 'Test Discussion']);

    $this->get(route('topics.show', $topic))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('topics/show')
            ->where('topic.id', $topic->id)
            ->has('discussions.data', 1)
            ->where('discussions.data.0.title', 'Test Discussion')
        );
});

test('authenticated user can access create discussion page', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create();

    $this->actingAs($user)
        ->get(route('discussions.create', $topic))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('discussions/create')
            ->where('topic.id', $topic->id)
            ->has('locations')
        );
});

test('authenticated user can store a discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('discussions.store'), [
            'topic_id' => $topic->id,
            'title' => 'My Discussion',
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Hello world']]]],
        ]);

    $discussion = Discussion::query()->where('title', 'My Discussion')->first();

    expect($discussion)->not->toBeNull();
    expect($discussion->slug)->toBe('my-discussion');
    expect($discussion->user_id)->toBe($user->id);
    expect($discussion->topic_id)->toBe($topic->id);

    $response->assertRedirect(route('discussions.show', [$topic, $discussion]));
});

test('discussion can be stored with a location', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create();
    $location = Location::factory()->create();

    $this->actingAs($user)
        ->post(route('discussions.store'), [
            'topic_id' => $topic->id,
            'location_id' => $location->id,
            'title' => 'Located Discussion',
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Content']]]],
        ]);

    $discussion = Discussion::query()->where('title', 'Located Discussion')->first();
    expect($discussion->location_id)->toBe($location->id);
});

test('discussion slug is unique within a topic', function () {
    $topic = Topic::factory()->create();
    Discussion::factory()->create(['topic_id' => $topic->id, 'title' => 'Same Title']);

    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('discussions.store'), [
            'topic_id' => $topic->id,
            'title' => 'Same Title',
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Content']]]],
        ]);

    $discussions = Discussion::query()->where('topic_id', $topic->id)->get();
    expect($discussions)->toHaveCount(2);
    expect($discussions->pluck('slug')->unique())->toHaveCount(2);
});

test('guest can view a discussion in a public topic', function () {
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Public]);
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $this->get(route('discussions.show', [$topic, $discussion]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('discussions/show')
            ->where('discussion.id', $discussion->id)
            ->where('auth.user', null)
        );
});

test('user can view a discussion', function () {
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $this->get(route('discussions.show', [$topic, $discussion]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('discussions/show')
            ->where('discussion.id', $discussion->id)
        );
});

test('owner can edit their discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id, 'user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('discussions.edit', [$topic, $discussion]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('discussions/edit')
        );
});

test('non-owner cannot edit a discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $this->actingAs($user)
        ->get(route('discussions.edit', [$topic, $discussion]))
        ->assertForbidden();
});

test('admin can edit any discussion', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $this->actingAs($admin)
        ->get(route('discussions.edit', [$topic, $discussion]))
        ->assertSuccessful();
});

test('owner can update their discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
        'title' => 'Old Title',
    ]);

    $this->actingAs($user)
        ->patch(route('discussions.update', [$topic, $discussion]), [
            'title' => 'New Title',
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Updated']]]],
        ]);

    $discussion->refresh();
    expect($discussion->title)->toBe('New Title');
});

test('owner can delete their discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id, 'user_id' => $user->id]);
    $discussionId = $discussion->id;

    $response = $this->actingAs($user)
        ->delete(route('discussions.destroy', [$topic, $discussion]));

    $response->assertRedirect(route('topics.show', $topic));
    expect(Discussion::find($discussionId))->toBeNull();
});

test('non-owner cannot delete a discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $this->actingAs($user)
        ->delete(route('discussions.destroy', [$topic, $discussion]))
        ->assertForbidden();
});

test('admin can delete any discussion', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    $discussionId = $discussion->id;

    $this->actingAs($admin)
        ->delete(route('discussions.destroy', [$topic, $discussion]));

    expect(Discussion::find($discussionId))->toBeNull();
});

// --- Validation ---

test('title is required when storing a discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create();

    $this->actingAs($user)
        ->post(route('discussions.store'), [
            'topic_id' => $topic->id,
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Content']]]],
        ])
        ->assertSessionHasErrors('title');
});

test('body is required when storing a discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create();

    $this->actingAs($user)
        ->post(route('discussions.store'), [
            'topic_id' => $topic->id,
            'title' => 'Some Title',
        ])
        ->assertSessionHasErrors('body');
});

test('topic_id is required when storing a discussion', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('discussions.store'), [
            'title' => 'Some Title',
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Content']]]],
        ])
        ->assertSessionHasErrors('topic_id');
});

test('suspended user cannot create a discussion', function () {
    $user = User::factory()->suspended()->create();
    $topic = Topic::factory()->create();

    $this->actingAs($user)
        ->post(route('discussions.store'), [
            'topic_id' => $topic->id,
            'title' => 'Test',
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Content']]]],
        ])
        ->assertForbidden();
});

test('suspended user cannot update a discussion', function () {
    $user = User::factory()->suspended()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id, 'user_id' => $user->id]);

    $this->actingAs($user)
        ->patch(route('discussions.update', [$topic, $discussion]), [
            'title' => 'Updated',
            'body' => [['type' => 'paragraph', 'children' => [['text' => 'Content']]]],
        ])
        ->assertForbidden();
});
