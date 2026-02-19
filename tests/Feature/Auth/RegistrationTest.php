<?php

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('username is required for registration', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('username');
});

test('username must be unique', function () {
    \App\Models\User::factory()->create(['username' => 'taken']);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'taken',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('username');
});

test('username must be at least 3 characters', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'ab',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('username');
});

test('username must only contain alphanumeric characters, dashes, and underscores', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'invalid username!',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('username');
});

test('new user is assigned the user role by default', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = \App\Models\User::where('email', 'test@example.com')->first();

    expect($user->role)->toBe(\App\Enums\UserRole::User);
});
