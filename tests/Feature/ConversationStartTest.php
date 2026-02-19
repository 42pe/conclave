<?php

use App\Models\Conversation;
use App\Models\User;
use App\Services\PostHogService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $mock = Mockery::mock(PostHogService::class);
    $mock->shouldReceive('capture')->byDefault();
    $mock->shouldReceive('identify')->byDefault();
    app()->instance(PostHogService::class, $mock);
});

it('redirects guest to login', function () {
    $user = User::factory()->create();

    $this->get("/conversations/start/{$user->id}")
        ->assertRedirect(route('login'));
});

it('creates a new conversation and redirects', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $response = $this->actingAs($sender)
        ->get("/conversations/start/{$recipient->id}");

    $conversation = Conversation::first();
    expect($conversation)->not->toBeNull();
    expect($conversation->participants)->toHaveCount(2);
    $response->assertRedirect(route('conversations.show', $conversation));
});

it('redirects to existing conversation if one exists', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = Conversation::create();
    $conversation->participants()->attach([$sender->id, $recipient->id]);

    $response = $this->actingAs($sender)
        ->get("/conversations/start/{$recipient->id}");

    $response->assertRedirect(route('conversations.show', $conversation));
    expect(Conversation::count())->toBe(1);
});

it('prevents starting a conversation with yourself', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get("/conversations/start/{$user->id}")
        ->assertForbidden();
});

it('returns 404 for deleted users', function () {
    $sender = User::factory()->create();
    $deleted = User::factory()->deleted()->create();

    $this->actingAs($sender)
        ->get("/conversations/start/{$deleted->id}")
        ->assertNotFound();
});

it('shows send message button on other user profiles', function () {
    $viewer = User::factory()->create();
    $profileUser = User::factory()->create();

    $this->actingAs($viewer)
        ->get("/users/{$profileUser->username}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('users/show')
            ->where('profileUser.id', $profileUser->id)
        );
});
