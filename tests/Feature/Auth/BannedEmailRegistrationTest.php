<?php

use App\Models\BannedEmail;
use App\Models\User;

test('registration with banned email is rejected', function () {
    BannedEmail::factory()->create(['email' => 'banned@example.com']);

    $response = $this->post(route('register.store'), [
        'name' => 'Banned User',
        'username' => 'banneduser',
        'email' => 'banned@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('registration with non-banned email succeeds', function () {
    BannedEmail::factory()->create(['email' => 'other@example.com']);

    $response = $this->post(route('register.store'), [
        'name' => 'Good User',
        'username' => 'gooduser',
        'email' => 'allowed@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('banned email check is case-insensitive', function () {
    BannedEmail::factory()->create(['email' => 'banned@example.com']);

    $response = $this->post(route('register.store'), [
        'name' => 'Sneaky User',
        'username' => 'sneakyuser',
        'email' => 'BANNED@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});
