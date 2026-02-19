<?php

use App\Enums\TopicVisibility;
use App\Models\Topic;
use App\Models\User;

test('admin can view topics index page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.topics.index'));

    $response->assertOk();
});

test('admin can view create topic page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.topics.create'));

    $response->assertOk();
});

test('admin can create a topic with all fields', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => 'General Discussion',
            'description' => 'A place for general discussion.',
            'icon' => 'message-circle',
            'visibility' => TopicVisibility::Public->value,
            'sort_order' => 1,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $topic = Topic::where('title', 'General Discussion')->first();
    expect($topic)->not->toBeNull();
    expect($topic->description)->toBe('A place for general discussion.');
    expect($topic->icon)->toBe('message-circle');
    expect($topic->visibility)->toBe(TopicVisibility::Public);
    expect($topic->sort_order)->toBe(1);
    expect($topic->created_by)->toBe($admin->id);
});

test('topic slug is auto-generated from title', function () {
    $admin = User::factory()->admin()->create();

    $this
        ->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => 'General Discussion',
            'description' => 'A place for general discussion.',
            'visibility' => TopicVisibility::Public->value,
            'sort_order' => 0,
        ]);

    $topic = Topic::where('title', 'General Discussion')->first();
    expect($topic->slug)->toBe('general-discussion');
});

test('topic slug must be unique', function () {
    $admin = User::factory()->admin()->create();
    Topic::factory()->create(['title' => 'General Discussion', 'slug' => 'general-discussion', 'created_by' => $admin->id]);

    $this
        ->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => 'General Discussion',
            'description' => 'Another topic with same title.',
            'visibility' => TopicVisibility::Public->value,
            'sort_order' => 0,
        ]);

    $topics = Topic::where('title', 'General Discussion')->get();
    expect($topics)->toHaveCount(2);
    expect($topics->pluck('slug')->unique())->toHaveCount(2);
});

test('admin can view edit topic page', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->create(['created_by' => $admin->id]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.topics.edit', $topic));

    $response->assertOk();
});

test('admin can update a topic', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->create(['created_by' => $admin->id]);

    $response = $this
        ->actingAs($admin)
        ->put(route('admin.topics.update', $topic), [
            'title' => 'Updated Title',
            'description' => 'Updated description.',
            'icon' => 'star',
            'visibility' => TopicVisibility::Private->value,
            'sort_order' => 5,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $topic->refresh();
    expect($topic->title)->toBe('Updated Title');
    expect($topic->description)->toBe('Updated description.');
    expect($topic->icon)->toBe('star');
    expect($topic->visibility)->toBe(TopicVisibility::Private);
    expect($topic->sort_order)->toBe(5);
});

test('admin can delete a topic', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->create(['created_by' => $admin->id]);

    $response = $this
        ->actingAs($admin)
        ->delete(route('admin.topics.destroy', $topic));

    $response->assertRedirect();

    expect(Topic::find($topic->id))->toBeNull();
});

test('validation requires title when creating topic', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => '',
            'visibility' => TopicVisibility::Public->value,
            'sort_order' => 0,
        ]);

    $response->assertSessionHasErrors('title');
});

test('validation requires valid visibility enum when creating topic', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => 'Test Topic',
            'visibility' => 'invalid-visibility',
            'sort_order' => 0,
        ]);

    $response->assertSessionHasErrors('visibility');
});

test('non-admin users get 403 on topics index', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('admin.topics.index'));

    $response->assertForbidden();
});

test('non-admin users get 403 on topic create', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('admin.topics.store'), [
            'title' => 'Sneaky Topic',
            'visibility' => TopicVisibility::Public->value,
            'sort_order' => 0,
        ]);

    $response->assertForbidden();
});

test('non-admin users get 403 on topic update', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $topic = Topic::factory()->create(['created_by' => $admin->id]);

    $response = $this
        ->actingAs($user)
        ->put(route('admin.topics.update', $topic), [
            'title' => 'Hacked Title',
            'visibility' => TopicVisibility::Public->value,
            'sort_order' => 0,
        ]);

    $response->assertForbidden();
});

test('non-admin users get 403 on topic delete', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $topic = Topic::factory()->create(['created_by' => $admin->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('admin.topics.destroy', $topic));

    $response->assertForbidden();
});

test('moderators cannot access admin topic management', function () {
    $moderator = User::factory()->moderator()->create();

    $response = $this
        ->actingAs($moderator)
        ->get(route('admin.topics.index'));

    $response->assertForbidden();
});

test('unauthenticated users get redirected to login on admin topics', function () {
    $response = $this->get(route('admin.topics.index'));

    $response->assertRedirect(route('login'));
});

test('unauthenticated users get redirected to login on topic store', function () {
    $response = $this->post(route('admin.topics.store'), [
        'title' => 'Test Topic',
        'visibility' => TopicVisibility::Public->value,
        'sort_order' => 0,
    ]);

    $response->assertRedirect(route('login'));
});
