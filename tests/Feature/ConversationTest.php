<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function validMessageBody(): array
{
    return [
        ['type' => 'paragraph', 'children' => [['text' => 'Hello there!']]],
    ];
}

function createConversationBetween(User $userA, User $userB): Conversation
{
    $conversation = Conversation::create();
    $conversation->participants()->attach([$userA->id, $userB->id]);

    return $conversation;
}

// === Index ===

it('shows conversations list for authenticated user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationBetween($user, $other);
    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $other->id,
    ]);

    $response = $this->actingAs($user)->get('/messages');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('messages/index')
        ->has('conversations.data', 1)
    );
});

it('requires authentication to view messages', function () {
    $response = $this->get('/messages');

    $response->assertRedirect('/login');
});

it('does not show conversations user is not part of', function () {
    $user = User::factory()->create();
    $otherA = User::factory()->create();
    $otherB = User::factory()->create();
    createConversationBetween($otherA, $otherB);

    $response = $this->actingAs($user)->get('/messages');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('conversations.data', 0)
    );
});

// === Show ===

it('shows a conversation with messages', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationBetween($user, $other);
    Message::factory()->count(3)->create([
        'conversation_id' => $conversation->id,
        'user_id' => $other->id,
    ]);

    $response = $this->actingAs($user)->get("/conversations/{$conversation->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('messages/show')
        ->has('messages.data', 3)
    );
});

it('prevents non-participant from viewing conversation', function () {
    $user = User::factory()->create();
    $otherA = User::factory()->create();
    $otherB = User::factory()->create();
    $conversation = createConversationBetween($otherA, $otherB);

    $response = $this->actingAs($user)->get("/conversations/{$conversation->id}");

    $response->assertForbidden();
});

it('marks conversation as read when viewed', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationBetween($user, $other);
    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $other->id,
    ]);

    $this->actingAs($user)->get("/conversations/{$conversation->id}");

    $participant = $conversation->participants()->where('user_id', $user->id)->first();
    expect($participant->pivot->last_read_at)->not->toBeNull();
});

// === Store Conversation ===

it('creates a new conversation and sends first message', function () {
    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $response = $this->actingAs($user)->post('/conversations', [
        'recipient_id' => $recipient->id,
        'body' => validMessageBody(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('conversations', 1);
    $this->assertDatabaseCount('messages', 1);
    $this->assertDatabaseHas('conversation_participants', ['user_id' => $user->id]);
    $this->assertDatabaseHas('conversation_participants', ['user_id' => $recipient->id]);
});

it('reuses existing conversation between users', function () {
    $user = User::factory()->create();
    $recipient = User::factory()->create();
    $existing = createConversationBetween($user, $recipient);

    $response = $this->actingAs($user)->post('/conversations', [
        'recipient_id' => $recipient->id,
        'body' => validMessageBody(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('conversations', 1);
    $this->assertDatabaseHas('messages', ['conversation_id' => $existing->id]);
});

it('prevents starting conversation with yourself', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/conversations', [
        'recipient_id' => $user->id,
        'body' => validMessageBody(),
    ]);

    $response->assertSessionHasErrors('recipient_id');
});

it('prevents suspended user from starting conversation', function () {
    $user = User::factory()->suspended()->create();
    $recipient = User::factory()->create();

    $response = $this->actingAs($user)->post('/conversations', [
        'recipient_id' => $recipient->id,
        'body' => validMessageBody(),
    ]);

    $response->assertForbidden();
});

it('requires authentication to start conversation', function () {
    $recipient = User::factory()->create();

    $response = $this->post('/conversations', [
        'recipient_id' => $recipient->id,
        'body' => validMessageBody(),
    ]);

    $response->assertRedirect('/login');
});
