<?php

use App\Models\BannedEmail;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('prevents registration with a banned email', function () {
    BannedEmail::factory()->create(['email' => 'banned@example.com']);

    $response = $this->post('/register', [
        'name' => 'Bad User',
        'username' => 'baduser',
        'email' => 'banned@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseMissing('users', ['email' => 'banned@example.com']);
});

it('allows registration with a non-banned email', function () {
    BannedEmail::factory()->create(['email' => 'other@example.com']);

    $response = $this->post('/register', [
        'name' => 'Good User',
        'username' => 'gooduser',
        'email' => 'allowed@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('users', ['email' => 'allowed@example.com']);
});

it('checks banned emails case-insensitively', function () {
    BannedEmail::factory()->create(['email' => 'banned@example.com']);

    $response = $this->post('/register', [
        'name' => 'Bad User',
        'username' => 'baduser',
        'email' => 'BANNED@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
});
