<?php

use App\Models\User;

// --- View notification preferences ---

test('user can view notification preferences page', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('notifications.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/notifications', false)
    );
});

test('unauthenticated user is redirected to login', function () {
    $response = $this->get(route('notifications.edit'));

    $response->assertRedirect(route('login'));
});

// --- Update notification preferences ---

test('user can update notify_replies preference', function () {
    $user = User::factory()->create(['notify_replies' => true]);

    $response = $this
        ->actingAs($user)
        ->patch(route('notifications.update'), [
            'notify_replies' => false,
            'notify_messages' => true,
            'notify_mentions' => true,
        ]);

    $response->assertRedirect(route('notifications.edit'));
    $response->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->notify_replies)->toBeFalse();
    expect($user->notify_messages)->toBeTrue();
    expect($user->notify_mentions)->toBeTrue();
});

test('user can update notify_messages preference', function () {
    $user = User::factory()->create(['notify_messages' => true]);

    $response = $this
        ->actingAs($user)
        ->patch(route('notifications.update'), [
            'notify_replies' => true,
            'notify_messages' => false,
            'notify_mentions' => true,
        ]);

    $response->assertRedirect(route('notifications.edit'));
    $response->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->notify_replies)->toBeTrue();
    expect($user->notify_messages)->toBeFalse();
    expect($user->notify_mentions)->toBeTrue();
});

test('user can update notify_mentions preference', function () {
    $user = User::factory()->create(['notify_mentions' => true]);

    $response = $this
        ->actingAs($user)
        ->patch(route('notifications.update'), [
            'notify_replies' => true,
            'notify_messages' => true,
            'notify_mentions' => false,
        ]);

    $response->assertRedirect(route('notifications.edit'));
    $response->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->notify_replies)->toBeTrue();
    expect($user->notify_messages)->toBeTrue();
    expect($user->notify_mentions)->toBeFalse();
});

// --- Defaults ---

test('all notification preferences default to true for new users', function () {
    $user = User::factory()->create();
    $user->refresh();

    expect($user->notify_replies)->toBeTrue();
    expect($user->notify_messages)->toBeTrue();
    expect($user->notify_mentions)->toBeTrue();
});
