<?php

use App\Models\User;

test('privacy settings page loads for authenticated user', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('privacy.edit'));

    $response->assertOk();
});

test('user can update all privacy settings to true', function () {
    $user = User::factory()->create([
        'show_real_name' => false,
        'show_email' => false,
        'show_in_directory' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('privacy.update'), [
            'show_real_name' => true,
            'show_email' => true,
            'show_in_directory' => true,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('privacy.edit'));

    $user->refresh();
    expect($user->show_real_name)->toBeTrue();
    expect($user->show_email)->toBeTrue();
    expect($user->show_in_directory)->toBeTrue();
});

test('user can update all privacy settings to false', function () {
    $user = User::factory()->create([
        'show_real_name' => true,
        'show_email' => true,
        'show_in_directory' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('privacy.update'), [
            'show_real_name' => false,
            'show_email' => false,
            'show_in_directory' => false,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('privacy.edit'));

    $user->refresh();
    expect($user->show_real_name)->toBeFalse();
    expect($user->show_email)->toBeFalse();
    expect($user->show_in_directory)->toBeFalse();
});

test('privacy settings persist after update', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('privacy.update'), [
            'show_real_name' => false,
            'show_email' => true,
            'show_in_directory' => false,
        ]);

    // Fetch user fresh from database
    $freshUser = User::find($user->id);
    expect($freshUser->show_real_name)->toBeFalse();
    expect($freshUser->show_email)->toBeTrue();
    expect($freshUser->show_in_directory)->toBeFalse();
});

test('guest cannot access privacy page', function () {
    $response = $this->get(route('privacy.edit'));

    $response->assertRedirect(route('login'));
});

test('guest cannot update privacy settings', function () {
    $response = $this->patch(route('privacy.update'), [
        'show_real_name' => true,
        'show_email' => true,
        'show_in_directory' => true,
    ]);

    $response->assertRedirect(route('login'));
});

test('invalid non-boolean values are rejected', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('privacy.update'), [
            'show_real_name' => 'not-a-boolean',
            'show_email' => 'yes',
            'show_in_directory' => 'no',
        ]);

    $response->assertSessionHasErrors(['show_real_name', 'show_email', 'show_in_directory']);
});
