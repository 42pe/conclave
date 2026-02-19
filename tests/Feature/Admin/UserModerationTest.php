<?php

use App\Enums\UserRole;
use App\Models\BannedEmail;
use App\Models\User;

test('admin can view users management page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.users.index'));

    $response->assertOk();
});

test('admin can view create user page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.users.create'));

    $response->assertOk();
});

test('admin can create a new user with all fields', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'New User',
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::User->value,
        ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'username' => 'newuser',
        'email' => 'newuser@example.com',
        'role' => UserRole::User->value,
    ]);
});

test('admin can suspend a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.users.suspend', $user));

    $response->assertRedirect();

    $user->refresh();
    expect($user->is_suspended)->toBeTrue();
});

test('admin can unsuspend a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->suspended()->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.users.unsuspend', $user));

    $response->assertRedirect();

    $user->refresh();
    expect($user->is_suspended)->toBeFalse();
});

test('admin can ban a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email' => 'banned@example.com']);

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.users.ban', $user));

    $response->assertRedirect(route('admin.users.index'));

    $user->refresh();
    expect($user->is_deleted)->toBeTrue();
    expect($user->email)->toBe("deleted_{$user->id}@deleted.local");

    $this->assertDatabaseHas('banned_emails', [
        'email' => 'banned@example.com',
        'user_id' => $user->id,
        'banned_by' => $admin->id,
    ]);
});

test('admin can ban a user with reason', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email' => 'spammer@example.com']);

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.users.ban', $user), [
            'reason' => 'Spamming the forum',
        ]);

    $response->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('banned_emails', [
        'email' => 'spammer@example.com',
        'reason' => 'Spamming the forum',
    ]);
});

test('admin can delete/anonymize a user without banning email', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'preferred_name' => 'Johnny',
        'bio' => 'Some bio',
        'avatar_path' => 'avatars/test.jpg',
    ]);
    $originalEmail = $user->email;

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.users.delete', $user));

    $response->assertRedirect(route('admin.users.index'));

    $user->refresh();
    expect($user->is_deleted)->toBeTrue();
    expect($user->name)->toBe('Deleted User');
    expect($user->first_name)->toBeNull();
    expect($user->last_name)->toBeNull();
    expect($user->preferred_name)->toBeNull();
    expect($user->bio)->toBeNull();
    expect($user->avatar_path)->toBeNull();
    expect($user->email)->toBe("deleted_{$user->id}@deleted.local");
    expect($user->deleted_at)->not->toBeNull();

    $this->assertDatabaseMissing('banned_emails', [
        'email' => $originalEmail,
    ]);
});

test('cannot suspend an admin user', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.users.suspend', $otherAdmin));

    $response->assertForbidden();

    $otherAdmin->refresh();
    expect($otherAdmin->is_suspended)->toBeFalse();
});

test('cannot ban an admin user', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.users.ban', $otherAdmin));

    $response->assertForbidden();

    $otherAdmin->refresh();
    expect($otherAdmin->is_deleted)->toBeFalse();
});

test('cannot delete an admin user', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.users.delete', $otherAdmin));

    $response->assertForbidden();

    $otherAdmin->refresh();
    expect($otherAdmin->is_deleted)->toBeFalse();
});

test('non-admin users get 403 on all moderation routes', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.users.store'), [])->assertForbidden();
    $this->actingAs($user)->post(route('admin.users.suspend', $target))->assertForbidden();
    $this->actingAs($user)->post(route('admin.users.unsuspend', $target))->assertForbidden();
    $this->actingAs($user)->post(route('admin.users.ban', $target))->assertForbidden();
    $this->actingAs($user)->post(route('admin.users.delete', $target))->assertForbidden();
});

test('moderator gets 403 on moderation routes', function () {
    $moderator = User::factory()->moderator()->create();
    $target = User::factory()->create();

    $this->actingAs($moderator)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($moderator)->post(route('admin.users.suspend', $target))->assertForbidden();
    $this->actingAs($moderator)->post(route('admin.users.ban', $target))->assertForbidden();
    $this->actingAs($moderator)->post(route('admin.users.delete', $target))->assertForbidden();
});

test('unauthenticated users get redirected to login on moderation routes', function () {
    $user = User::factory()->create();

    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    $this->post(route('admin.users.store'), [])->assertRedirect(route('login'));
    $this->post(route('admin.users.suspend', $user))->assertRedirect(route('login'));
    $this->post(route('admin.users.ban', $user))->assertRedirect(route('login'));
    $this->post(route('admin.users.delete', $user))->assertRedirect(route('login'));
});
