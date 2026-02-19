<?php

use App\Enums\TopicVisibility;
use App\Models\Discussion;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// === Profile View ===

it('shows a user profile page', function () {
    $user = User::factory()->create();

    $response = $this->get("/users/{$user->username}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show')
        ->has('profileUser')
        ->where('profileUser.username', $user->username)
    );
});

it('returns 404 for non-existent username', function () {
    $response = $this->get('/users/nonexistent-user');

    $response->assertNotFound();
});

it('shows deleted user profile with limited info', function () {
    $user = User::factory()->deleted()->create();

    $response = $this->get("/users/{$user->username}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('profileUser.is_deleted', true)
        ->where('profileUser.display_name', 'Deleted User')
    );
});

it('shows user discussions on profile', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Public]);
    Discussion::factory()->count(3)->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    $response = $this->get("/users/{$user->username}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('discussions.data', 3)
    );
});

it('shows user replies on profile', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Public]);
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    Reply::factory()->count(2)->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
    ]);

    $response = $this->get("/users/{$user->username}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('replies.data', 2)
    );
});

it('respects privacy setting for email visibility', function () {
    $user = User::factory()->create([
        'show_email' => false,
        'email' => 'secret@example.com',
    ]);

    $response = $this->get("/users/{$user->username}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('profileUser.show_email', false)
    );
});

it('shows email when privacy allows', function () {
    $user = User::factory()->create([
        'show_email' => true,
        'email' => 'public@example.com',
    ]);

    $response = $this->get("/users/{$user->username}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('profileUser.show_email', true)
        ->where('profileUser.email', 'public@example.com')
    );
});

it('paginates discussions on profile', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create(['visibility' => TopicVisibility::Public]);
    Discussion::factory()->count(15)->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    $response = $this->get("/users/{$user->username}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('discussions.data', 10)
    );
});

it('shows role badge for admin users', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->get("/users/{$admin->username}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('profileUser.role', 'admin')
    );
});
