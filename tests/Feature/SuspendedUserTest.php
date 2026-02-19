<?php

use App\Models\Discussion;
use App\Models\Topic;
use App\Models\User;

test('suspended user can browse discussions', function () {
    $user = User::factory()->suspended()->create();
    $topic = Topic::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('topics.show', $topic));

    $response->assertOk();
});

test('suspended user can view a discussion', function () {
    $user = User::factory()->suspended()->create();
    $topic = Topic::factory()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('topics.discussions.show', [$topic, $discussion]));

    $response->assertOk();
});

test('suspended user cannot create a discussion', function () {
    $user = User::factory()->suspended()->create();
    $topic = Topic::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('topics.discussions.store', $topic), [
            'title' => 'Test Discussion',
            'body' => json_encode([['type' => 'paragraph', 'children' => [['text' => 'Test']]]]),
        ]);

    $response->assertForbidden();
});

test('suspended user cannot access create discussion page', function () {
    $user = User::factory()->suspended()->create();
    $topic = Topic::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('topics.discussions.create', $topic));

    $response->assertForbidden();
});

test('suspended user cannot create a reply', function () {
    $user = User::factory()->suspended()->create();
    $discussion = Discussion::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => json_encode([['type' => 'paragraph', 'children' => [['text' => 'Test reply']]]]),
        ]);

    $response->assertForbidden();
});

test('suspended user can still log in', function () {
    $user = User::factory()->suspended()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('suspended user can still update their profile', function () {
    $user = User::factory()->suspended()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'username' => $user->username,
            'email' => $user->email,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
});
