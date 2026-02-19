<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function validMsgBody(): array
{
    return [
        ['type' => 'paragraph', 'children' => [['text' => 'A message.']]],
    ];
}

function createConvBetween(User $userA, User $userB): Conversation
{
    $conversation = Conversation::create();
    $conversation->participants()->attach([$userA->id, $userB->id]);

    return $conversation;
}

// === Store Message ===

it('allows participant to send a message', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConvBetween($user, $other);

    $response = $this->actingAs($user)->post('/messages', [
        'conversation_id' => $conversation->id,
        'body' => validMsgBody(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
    ]);
});

it('prevents non-participant from sending a message', function () {
    $user = User::factory()->create();
    $otherA = User::factory()->create();
    $otherB = User::factory()->create();
    $conversation = createConvBetween($otherA, $otherB);

    $response = $this->actingAs($user)->post('/messages', [
        'conversation_id' => $conversation->id,
        'body' => validMsgBody(),
    ]);

    $response->assertForbidden();
});

it('prevents suspended user from sending a message', function () {
    $user = User::factory()->suspended()->create();
    $other = User::factory()->create();
    $conversation = createConvBetween($user, $other);

    $response = $this->actingAs($user)->post('/messages', [
        'conversation_id' => $conversation->id,
        'body' => validMsgBody(),
    ]);

    $response->assertForbidden();
});

it('requires a valid conversation_id', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/messages', [
        'conversation_id' => 99999,
        'body' => validMsgBody(),
    ]);

    $response->assertSessionHasErrors('conversation_id');
});

it('requires a body', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConvBetween($user, $other);

    $response = $this->actingAs($user)->post('/messages', [
        'conversation_id' => $conversation->id,
    ]);

    $response->assertSessionHasErrors('body');
});

it('validates body is a valid slate document', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConvBetween($user, $other);

    $response = $this->actingAs($user)->post('/messages', [
        'conversation_id' => $conversation->id,
        'body' => [['invalid' => 'structure']],
    ]);

    $response->assertSessionHasErrors('body');
});

it('requires authentication to send a message', function () {
    $response = $this->post('/messages', [
        'conversation_id' => 1,
        'body' => validMsgBody(),
    ]);

    $response->assertRedirect('/login');
});
