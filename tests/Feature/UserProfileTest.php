<?php

use App\Enums\UserRole;
use App\Models\Discussion;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;

// --- Profile page visibility ---

test('anyone can view a user profile by username', function () {
    $user = User::factory()->create();

    $response = $this->get(route('users.show', $user->username));

    $response->assertOk();
});

test('profile page renders the correct Inertia component', function () {
    $user = User::factory()->create();

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
    );
});

test('profile shows display_name and username', function () {
    $user = User::factory()->create([
        'name' => 'Jane Smith',
        'username' => 'janesmith',
        'preferred_name' => null,
    ]);

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->where('profileUser.username', 'janesmith')
        ->where('profileUser.display_name', 'Jane Smith')
    );
});

test('profile shows preferred_name as display_name when set', function () {
    $user = User::factory()->create([
        'name' => 'Jane Smith',
        'preferred_name' => 'Janey',
    ]);

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->where('profileUser.display_name', 'Janey')
    );
});

test('profile shows bio when set', function () {
    $user = User::factory()->create([
        'bio' => 'I love coding and coffee.',
    ]);

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->where('profileUser.bio', 'I love coding and coffee.')
    );
});

// --- Privacy: real name ---

test('profile shows real name when show_real_name is true', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'show_real_name' => true,
    ]);

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->where('profileUser.first_name', 'Jane')
        ->where('profileUser.last_name', 'Smith')
    );
});

test('profile hides real name when show_real_name is false', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'show_real_name' => false,
    ]);

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->missing('profileUser.first_name')
        ->missing('profileUser.last_name')
    );
});

// --- Privacy: email ---

test('profile shows email when show_email is true', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'show_email' => true,
    ]);

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->where('profileUser.email', 'jane@example.com')
    );
});

test('profile hides email when show_email is false', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'show_email' => false,
    ]);

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->missing('profileUser.email')
    );
});

// --- Discussions and replies ---

test('profile shows user discussions', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    Discussion::factory()->count(3)->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->has('discussions.data', 3)
    );
});

test('profile paginates user discussions', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    Discussion::factory()->count(15)->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->has('discussions.data', 10)
    );
});

test('profile shows user recent replies', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);
    Reply::factory()->count(3)->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
    ]);

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->has('recentReplies', 3)
    );
});

// --- Deleted user profile ---

test('deleted user profile shows Deleted User as display_name', function () {
    $user = User::factory()->deleted()->create([
        'name' => 'Jane Smith',
        'preferred_name' => 'Janey',
    ]);

    $response = $this->get(route('users.show', $user->username));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->where('profileUser.display_name', 'Deleted User')
        ->where('profileUser.is_deleted', true)
    );
});

test('deleted user profile hides personal info', function () {
    $user = User::factory()->deleted()->create([
        'bio' => 'My bio',
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
        'show_real_name' => true,
        'show_email' => true,
    ]);

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->missing('profileUser.bio')
        ->missing('profileUser.first_name')
        ->missing('profileUser.last_name')
        ->missing('profileUser.email')
    );
});

test('deleted user profile hides avatar and role', function () {
    $user = User::factory()->deleted()->create([
        'avatar_path' => 'avatars/test.jpg',
    ]);

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->where('profileUser.avatar_path', null)
        ->where('profileUser.role', null)
    );
});

// --- Suspended user ---

test('suspended user profile is still viewable', function () {
    $user = User::factory()->suspended()->create();

    $response = $this->get(route('users.show', $user->username));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->where('profileUser.is_suspended', true)
    );
});

// --- Admin/moderator role ---

test('admin role is shown on profile', function () {
    $user = User::factory()->admin()->create();

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->where('profileUser.role', UserRole::Admin)
    );
});

test('moderator role is shown on profile', function () {
    $user = User::factory()->moderator()->create();

    $response = $this->get(route('users.show', $user->username));

    $response->assertInertia(fn ($page) => $page
        ->component('users/show', false)
        ->where('profileUser.role', UserRole::Moderator)
    );
});

// --- Non-existent user ---

test('404 for non-existent username', function () {
    $response = $this->get(route('users.show', 'nonexistentuser999'));

    $response->assertNotFound();
});

// --- Authenticated user can also view profiles ---

test('authenticated user can view another user profile', function () {
    $viewer = User::factory()->create();
    $profileUser = User::factory()->create();

    $response = $this
        ->actingAs($viewer)
        ->get(route('users.show', $profileUser->username));

    $response->assertOk();
});
