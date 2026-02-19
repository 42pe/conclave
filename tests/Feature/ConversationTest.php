<?php

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;

// --- Helper ---

function validMessageBody(): array
{
    return [
        ['type' => 'paragraph', 'children' => [['text' => 'Hello, how are you?']]],
    ];
}

function createConversationBetween(User $user1, User $user2): Conversation
{
    $conversation = Conversation::factory()->create();
    $conversation->participants()->createMany([
        ['user_id' => $user1->id, 'last_read_at' => now()],
        ['user_id' => $user2->id],
    ]);

    return $conversation;
}

// --- Conversation index ---

test('authenticated user can view their conversations list', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationBetween($user, $other);
    Message::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('conversations.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('messages/index', false)
        ->has('conversations', 1)
    );
});

test('conversation list is ordered by latest message', function () {
    $user = User::factory()->create();
    $other1 = User::factory()->create();
    $other2 = User::factory()->create();

    $conv1 = createConversationBetween($user, $other1);
    Message::factory()->create([
        'conversation_id' => $conv1->id,
        'user_id' => $other1->id,
        'created_at' => now()->subHour(),
    ]);

    $conv2 = createConversationBetween($user, $other2);
    Message::factory()->create([
        'conversation_id' => $conv2->id,
        'user_id' => $other2->id,
        'created_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('conversations.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('messages/index', false)
        ->has('conversations', 2)
        ->where('conversations.0.id', $conv2->id)
        ->where('conversations.1.id', $conv1->id)
    );
});

test('conversation list shows unread count', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $conversation = createConversationBetween($user, $other);

    // Set user's last_read_at to the past
    $conversation->participants()
        ->where('user_id', $user->id)
        ->update(['last_read_at' => now()->subHour()]);

    // Create messages from the other user after last_read_at
    Message::factory()->count(3)->create([
        'conversation_id' => $conversation->id,
        'user_id' => $other->id,
        'created_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('conversations.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('messages/index', false)
        ->has('conversations', 1)
        ->where('conversations.0.unread_count', 3)
    );
});

test('user only sees conversations they participate in', function () {
    $user = User::factory()->create();
    $other1 = User::factory()->create();
    $other2 = User::factory()->create();

    // Conversation the user is part of
    $myConv = createConversationBetween($user, $other1);
    Message::factory()->create(['conversation_id' => $myConv->id, 'user_id' => $other1->id]);

    // Conversation the user is NOT part of
    $otherConv = createConversationBetween($other1, $other2);
    Message::factory()->create(['conversation_id' => $otherConv->id, 'user_id' => $other2->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('conversations.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('messages/index', false)
        ->has('conversations', 1)
        ->where('conversations.0.id', $myConv->id)
    );
});

test('unauthenticated user is redirected to login when viewing conversations', function () {
    $response = $this->get(route('conversations.index'));

    $response->assertRedirect(route('login'));
});

// --- Start a new conversation ---

test('user can start a new conversation with another user', function () {
    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('conversations.store'), [
            'recipient_id' => $recipient->id,
            'body' => validMessageBody(),
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $conversation = Conversation::between($user, $recipient);
    expect($conversation)->not->toBeNull();
    expect($conversation->messages)->toHaveCount(1);
    expect($conversation->messages->first()->user_id)->toBe($user->id);
});

test('starting conversation with existing recipient returns existing conversation', function () {
    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $existingConv = createConversationBetween($user, $recipient);

    $response = $this
        ->actingAs($user)
        ->post(route('conversations.store'), [
            'recipient_id' => $recipient->id,
            'body' => validMessageBody(),
        ]);

    $response->assertRedirect(route('conversations.show', $existingConv));
    $response->assertSessionHasNoErrors();

    // Should not create a new conversation
    expect(Conversation::count())->toBe(1);
    // But should add a new message
    expect($existingConv->messages()->count())->toBe(1);
});

test('cannot start conversation with self', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('conversations.store'), [
            'recipient_id' => $user->id,
            'body' => validMessageBody(),
        ]);

    $response->assertSessionHasErrors('recipient_id');
    expect(Conversation::count())->toBe(0);
});

test('cannot start conversation with deleted user', function () {
    $user = User::factory()->create();
    $deleted = User::factory()->deleted()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('conversations.store'), [
            'recipient_id' => $deleted->id,
            'body' => validMessageBody(),
        ]);

    $response->assertSessionHasErrors('recipient_id');
    expect(Conversation::count())->toBe(0);
});

test('cannot start conversation with non-existent user', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('conversations.store'), [
            'recipient_id' => 99999,
            'body' => validMessageBody(),
        ]);

    $response->assertSessionHasErrors('recipient_id');
});

test('conversation body validates as SlateDocument', function () {
    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('conversations.store'), [
            'recipient_id' => $recipient->id,
            'body' => [['type' => 'invalid-type', 'children' => [['text' => 'Test']]]],
        ]);

    $response->assertSessionHasErrors('body');
});

test('conversation body is required', function () {
    $user = User::factory()->create();
    $recipient = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('conversations.store'), [
            'recipient_id' => $recipient->id,
            'body' => null,
        ]);

    $response->assertSessionHasErrors('body');
});

test('unauthenticated user cannot start a conversation', function () {
    $recipient = User::factory()->create();

    $response = $this->post(route('conversations.store'), [
        'recipient_id' => $recipient->id,
        'body' => validMessageBody(),
    ]);

    $response->assertRedirect(route('login'));
});

// --- Show conversation ---

test('participant can view a conversation', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationBetween($user, $other);
    Message::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('conversations.show', $conversation));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('messages/show', false)
        ->has('conversation')
        ->has('messages', 1)
    );
});

test('non-participant cannot view a conversation', function () {
    $user = User::factory()->create();
    $other1 = User::factory()->create();
    $other2 = User::factory()->create();
    $conversation = createConversationBetween($other1, $other2);

    $response = $this
        ->actingAs($user)
        ->get(route('conversations.show', $conversation));

    $response->assertForbidden();
});

test('viewing conversation marks it as read', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createConversationBetween($user, $other);

    // Set last_read_at to the past
    $conversation->participants()
        ->where('user_id', $user->id)
        ->update(['last_read_at' => now()->subDay()]);

    $oldLastRead = $conversation->participants()
        ->where('user_id', $user->id)
        ->first()
        ->last_read_at;

    $this
        ->actingAs($user)
        ->get(route('conversations.show', $conversation));

    $newLastRead = ConversationParticipant::query()
        ->where('conversation_id', $conversation->id)
        ->where('user_id', $user->id)
        ->first()
        ->last_read_at;

    expect($newLastRead->isAfter($oldLastRead))->toBeTrue();
});

test('unauthenticated user is redirected to login when viewing a conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createConversationBetween($user1, $user2);

    $response = $this->get(route('conversations.show', $conversation));

    $response->assertRedirect(route('login'));
});

// --- Conversation::between() static method ---

test('Conversation::between() finds existing conversation between two users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createConversationBetween($user1, $user2);

    $found = Conversation::between($user1, $user2);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($conversation->id);
});

test('Conversation::between() works regardless of user argument order', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createConversationBetween($user1, $user2);

    $found1 = Conversation::between($user1, $user2);
    $found2 = Conversation::between($user2, $user1);

    expect($found1->id)->toBe($conversation->id);
    expect($found2->id)->toBe($conversation->id);
});

test('Conversation::between() returns null when no conversation exists', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $found = Conversation::between($user1, $user2);

    expect($found)->toBeNull();
});

// --- Model relationships ---

test('conversation has many messages', function () {
    $conversation = Conversation::factory()->create();
    Message::factory()->count(3)->create(['conversation_id' => $conversation->id]);

    expect($conversation->messages)->toHaveCount(3);
});

test('conversation belongs to many users via participants', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createConversationBetween($user1, $user2);

    expect($conversation->users)->toHaveCount(2);
    expect($conversation->users->pluck('id')->sort()->values()->all())
        ->toBe(collect([$user1->id, $user2->id])->sort()->values()->all());
});

test('conversation has latest message', function () {
    $conversation = Conversation::factory()->create();
    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'created_at' => now()->subHour(),
    ]);
    $latest = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'created_at' => now(),
    ]);

    expect($conversation->latestMessage->id)->toBe($latest->id);
});
