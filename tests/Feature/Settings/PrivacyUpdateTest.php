<?php

use App\Models\User;

test('privacy settings page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('privacy.edit'));

    $response->assertOk();
});

test('privacy settings can be updated', function () {
    $user = User::factory()->create([
        'show_real_name' => true,
        'show_email' => false,
        'show_in_directory' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('privacy.update'), [
            'show_real_name' => false,
            'show_email' => true,
            'show_in_directory' => false,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('privacy.edit'));

    $user->refresh();

    expect($user->show_real_name)->toBeFalse();
    expect($user->show_email)->toBeTrue();
    expect($user->show_in_directory)->toBeFalse();
});

test('privacy settings require boolean values', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('privacy.update'), [
            'show_real_name' => 'not-a-boolean',
            'show_email' => 'invalid',
            'show_in_directory' => 'wrong',
        ]);

    $response->assertSessionHasErrors(['show_real_name', 'show_email', 'show_in_directory']);
});

test('privacy settings fields are required', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('privacy.update'), []);

    $response->assertSessionHasErrors(['show_real_name', 'show_email', 'show_in_directory']);
});

test('unauthenticated user cannot view privacy settings', function () {
    $response = $this->get(route('privacy.edit'));

    $response->assertRedirect(route('login'));
});

test('unauthenticated user cannot update privacy settings', function () {
    $response = $this->patch(route('privacy.update'), [
        'show_real_name' => true,
        'show_email' => false,
        'show_in_directory' => true,
    ]);

    $response->assertRedirect(route('login'));
});

test('unverified user can view privacy settings', function () {
    $user = User::factory()->unverified()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('privacy.edit'));

    $response->assertOk();
});

test('unverified user can update privacy settings', function () {
    $user = User::factory()->unverified()->create([
        'show_real_name' => true,
        'show_email' => false,
        'show_in_directory' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('privacy.update'), [
            'show_real_name' => false,
            'show_email' => true,
            'show_in_directory' => false,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('privacy.edit'));

    $user->refresh();

    expect($user->show_real_name)->toBeFalse();
    expect($user->show_email)->toBeTrue();
    expect($user->show_in_directory)->toBeFalse();
});

test('individual privacy settings can be toggled', function () {
    $user = User::factory()->create([
        'show_real_name' => true,
        'show_email' => true,
        'show_in_directory' => true,
    ]);

    // Turn off only show_email
    $response = $this
        ->actingAs($user)
        ->patch(route('privacy.update'), [
            'show_real_name' => true,
            'show_email' => false,
            'show_in_directory' => true,
        ]);

    $response->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->show_real_name)->toBeTrue();
    expect($user->show_email)->toBeFalse();
    expect($user->show_in_directory)->toBeTrue();
});
