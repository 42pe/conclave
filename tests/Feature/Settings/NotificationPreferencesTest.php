<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('shows notification settings page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/notifications');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('settings/notifications'));
});

it('requires authentication to view notification settings', function () {
    $response = $this->get('/settings/notifications');

    $response->assertRedirect('/login');
});

it('updates notification preferences', function () {
    $user = User::factory()->create([
        'notify_replies' => true,
        'notify_messages' => true,
    ]);

    $response = $this->actingAs($user)->patch('/settings/notifications', [
        'notify_replies' => false,
        'notify_messages' => false,
    ]);

    $response->assertRedirect('/settings/notifications');

    $user->refresh();
    expect($user->notify_replies)->toBeFalse();
    expect($user->notify_messages)->toBeFalse();
});

it('can enable notification preferences', function () {
    $user = User::factory()->create([
        'notify_replies' => false,
        'notify_messages' => false,
    ]);

    $response = $this->actingAs($user)->patch('/settings/notifications', [
        'notify_replies' => true,
        'notify_messages' => true,
    ]);

    $response->assertRedirect('/settings/notifications');

    $user->refresh();
    expect($user->notify_replies)->toBeTrue();
    expect($user->notify_messages)->toBeTrue();
});

it('validates notification preferences are boolean', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch('/settings/notifications', [
        'notify_replies' => 'not-a-boolean',
        'notify_messages' => 'invalid',
    ]);

    $response->assertSessionHasErrors(['notify_replies', 'notify_messages']);
});

it('requires all notification fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch('/settings/notifications', []);

    $response->assertSessionHasErrors(['notify_replies', 'notify_messages']);
});
