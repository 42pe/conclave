<?php

use App\Models\BannedEmail;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// === Index ===

it('allows admin to view user list', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(3)->create();

    $response = $this->actingAs($admin)->get('/admin/users');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/users/index')
        ->has('users.data', 4) // 3 + admin
    );
});

it('prevents non-admin from viewing user list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/users');

    $response->assertForbidden();
});

it('allows admin to search users', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['username' => 'findme']);
    User::factory()->create(['username' => 'other']);

    $response = $this->actingAs($admin)->get('/admin/users?search=findme');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('users.data', 1)
    );
});

// === Create User ===

it('allows admin to view create user form', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/users/create');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('admin/users/create'));
});

it('allows admin to create a user', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/users', [
        'name' => 'New User',
        'username' => 'newuser',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'user',
    ]);

    $response->assertRedirect('/admin/users');
    $this->assertDatabaseHas('users', [
        'username' => 'newuser',
        'email' => 'new@example.com',
    ]);
});

it('allows admin to create a moderator', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/users', [
        'name' => 'New Mod',
        'username' => 'newmod',
        'email' => 'mod@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'moderator',
    ]);

    $response->assertRedirect('/admin/users');
    $this->assertDatabaseHas('users', [
        'username' => 'newmod',
        'role' => 'moderator',
    ]);
});

// === Suspend ===

it('allows admin to suspend a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/suspend");

    $response->assertRedirect();
    $user->refresh();
    expect($user->is_suspended)->toBeTrue();
});

it('allows admin to unsuspend a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->suspended()->create();

    $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/unsuspend");

    $response->assertRedirect();
    $user->refresh();
    expect($user->is_suspended)->toBeFalse();
});

// === Ban ===

it('allows admin to ban a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email' => 'ban@example.com']);

    $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/ban", [
        'reason' => 'Spam',
    ]);

    $response->assertRedirect('/admin/users');
    $user->refresh();
    expect($user->is_deleted)->toBeTrue();
    $this->assertDatabaseHas('banned_emails', [
        'email' => 'ban@example.com',
        'banned_by' => $admin->id,
        'reason' => 'Spam',
    ]);
});

it('banning anonymizes user data', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'email' => 'ban@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'bio' => 'Some bio',
    ]);

    $this->actingAs($admin)->post("/admin/users/{$user->id}/ban");

    $user->refresh();
    expect($user->name)->toBe('Deleted User');
    expect($user->first_name)->toBeNull();
    expect($user->last_name)->toBeNull();
    expect($user->bio)->toBeNull();
    expect($user->is_deleted)->toBeTrue();
    expect($user->email)->not->toBe('ban@example.com');
});

// === Delete ===

it('allows admin to delete (anonymize) a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $originalEmail = $user->email;

    $response = $this->actingAs($admin)->delete("/admin/users/{$user->id}");

    $response->assertRedirect('/admin/users');
    $user->refresh();
    expect($user->is_deleted)->toBeTrue();
    expect($user->name)->toBe('Deleted User');
    expect($user->email)->not->toBe($originalEmail);
});

it('delete does not add email to banned list', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email' => 'nodelete@example.com']);

    $this->actingAs($admin)->delete("/admin/users/{$user->id}");

    $this->assertDatabaseMissing('banned_emails', [
        'email' => 'nodelete@example.com',
    ]);
});

// === Authorization ===

// === Self-moderation prevention ===

it('prevents admin from suspending themselves', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post("/admin/users/{$admin->id}/suspend");

    $response->assertForbidden();
    $admin->refresh();
    expect($admin->is_suspended)->toBeFalse();
});

it('prevents admin from banning themselves', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post("/admin/users/{$admin->id}/ban");

    $response->assertForbidden();
    $admin->refresh();
    expect($admin->is_deleted)->toBeFalse();
});

it('prevents admin from deleting themselves', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->delete("/admin/users/{$admin->id}");

    $response->assertForbidden();
    $admin->refresh();
    expect($admin->is_deleted)->toBeFalse();
});

// === Authorization ===

it('prevents non-admin from suspending', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $response = $this->actingAs($user)->post("/admin/users/{$target->id}/suspend");

    $response->assertForbidden();
});

it('prevents non-admin from banning', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $response = $this->actingAs($user)->post("/admin/users/{$target->id}/ban");

    $response->assertForbidden();
});

it('prevents non-admin from deleting', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $response = $this->actingAs($user)->delete("/admin/users/{$target->id}");

    $response->assertForbidden();
});
