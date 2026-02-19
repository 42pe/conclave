<?php

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;

// --- Helper ---

function validMsgBody(): array
{
    return [
        ['type' => 'paragraph', 'children' => [['text' => 'This is a reply message.']]],
    ];
}

function createConversationWithParticipants(User $user1, User $user2): Conversation
{
    $conversation = Conversation::factory()->create();
    $conversation->participants()->createMany([
        ['user_id' => $user1->id, 'last_read_at' => now()],
        ['user_id' => $user2->id],
    ]);

    return $conversation;
}

// --- Sending messages ---

test('participant can send a message in conversation', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationWithParticipants($user, $other);

    $response = $this
        ->actingAs($user)
        ->post(route('messages.store', $conversation), [
            'body' => validMsgBody(),
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    expect($conversation->messages()->count())->toBe(1);
    expect($conversation->messages->first()->user_id)->toBe($user->id);
    expect($conversation->messages->first()->body)->toBeArray();
});

test('non-participant cannot send message', function () {
    $user = User::factory()->create();
    $other1 = User::factory()->create();
    $other2 = User::factory()->create();
    $conversation = createConversationWithParticipants($other1, $other2);

    $response = $this
        ->actingAs($user)
        ->post(route('messages.store', $conversation), [
            'body' => validMsgBody(),
        ]);

    $response->assertForbidden();
    expect($conversation->messages()->count())->toBe(0);
});

test('message body validates as SlateDocument', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationWithParticipants($user, $other);

    $response = $this
        ->actingAs($user)
        ->post(route('messages.store', $conversation), [
            'body' => [['type' => 'invalid-type', 'children' => [['text' => 'Test']]]],
        ]);

    $response->assertSessionHasErrors('body');
});

test('message body is required', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationWithParticipants($user, $other);

    $response = $this
        ->actingAs($user)
        ->post(route('messages.store', $conversation), [
            'body' => null,
        ]);

    $response->assertSessionHasErrors('body');
});

test('message is associated with correct conversation and user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationWithParticipants($user, $other);

    $this
        ->actingAs($user)
        ->post(route('messages.store', $conversation), [
            'body' => validMsgBody(),
        ]);

    $message = Message::first();
    expect($message->conversation_id)->toBe($conversation->id);
    expect($message->user_id)->toBe($user->id);
    expect($message->body)->toBeArray();
});

test('sending message updates sender last_read_at', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationWithParticipants($user, $other);

    // Set last_read_at to the past
    $conversation->participants()
        ->where('user_id', $user->id)
        ->update(['last_read_at' => now()->subDay()]);

    $oldLastRead = ConversationParticipant::query()
        ->where('conversation_id', $conversation->id)
        ->where('user_id', $user->id)
        ->first()
        ->last_read_at;

    $this
        ->actingAs($user)
        ->post(route('messages.store', $conversation), [
            'body' => validMsgBody(),
        ]);

    $newLastRead = ConversationParticipant::query()
        ->where('conversation_id', $conversation->id)
        ->where('user_id', $user->id)
        ->first()
        ->last_read_at;

    expect($newLastRead->isAfter($oldLastRead))->toBeTrue();
});

test('unauthenticated user cannot send message', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createConversationWithParticipants($user1, $user2);

    $response = $this->post(route('messages.store', $conversation), [
        'body' => validMsgBody(),
    ]);

    $response->assertRedirect(route('login'));
});

// --- Unread messages count via Inertia shared data ---

test('unread messages count is shared via Inertia', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationWithParticipants($user, $other);

    // User has no messages yet - count should be 0
    $response = $this
        ->actingAs($user)
        ->get(route('conversations.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('unread_messages_count', 0)
    );
});

test('unread messages count reflects unread conversations', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationWithParticipants($user, $other);

    // Clear user's last_read_at so all messages from other are "unread"
    $conversation->participants()
        ->where('user_id', $user->id)
        ->update(['last_read_at' => null]);

    // Other user sends a message
    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $other->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('conversations.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('unread_messages_count', 1)
    );
});

test('own messages do not count as unread', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationWithParticipants($user, $other);

    // Clear user's last_read_at
    $conversation->participants()
        ->where('user_id', $user->id)
        ->update(['last_read_at' => null]);

    // User sends their own message (should not count as unread)
    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('conversations.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('unread_messages_count', 0)
    );
});

// --- Model relationships ---

test('message belongs to a conversation', function () {
    $conversation = Conversation::factory()->create();
    $message = Message::factory()->create(['conversation_id' => $conversation->id]);

    expect($message->conversation->id)->toBe($conversation->id);
});

test('message belongs to a user', function () {
    $user = User::factory()->create();
    $message = Message::factory()->create(['user_id' => $user->id]);

    expect($message->user->id)->toBe($user->id);
});
