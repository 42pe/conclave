<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'username' => 'newusername',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->username)->toBe('newusername');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'username' => $user->username,
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    $user->refresh();
    expect($user->is_deleted)->toBeTrue();
    expect($user->name)->toBe('Deleted User');
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});

test('username can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => 'updatedname',
            'email' => $user->email,
        ]);

    $response->assertSessionHasNoErrors();

    expect($user->refresh()->username)->toBe('updatedname');
});

test('username must be unique when updating profile', function () {
    $existingUser = User::factory()->create(['username' => 'taken']);
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => 'taken',
            'email' => $user->email,
        ]);

    $response->assertSessionHasErrors('username');
});

test('user can keep their own username when updating profile', function () {
    $user = User::factory()->create(['username' => 'myname']);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'username' => 'myname',
            'email' => $user->email,
        ]);

    $response->assertSessionHasNoErrors();

    expect($user->refresh()->name)->toBe('Updated Name');
});

test('optional profile fields can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'preferred_name' => 'Johnny',
            'bio' => 'A short bio about myself.',
        ]);

    $response->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->first_name)->toBe('John');
    expect($user->last_name)->toBe('Doe');
    expect($user->preferred_name)->toBe('Johnny');
    expect($user->bio)->toBe('A short bio about myself.');
});

test('bio cannot exceed 1000 characters', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'bio' => str_repeat('a', 1001),
        ]);

    $response->assertSessionHasErrors('bio');
});
