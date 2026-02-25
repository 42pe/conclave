<?php

use App\Models\Discussion;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\MentionNotification;
use App\Services\MentionService;
use Illuminate\Support\Facades\Notification;

// --- Helper ---

function mentionNode(int $userId, string $username): array
{
    return [
        'type' => 'mention',
        'userId' => $userId,
        'username' => $username,
        'children' => [['text' => '']],
    ];
}

function documentWithMentions(array $mentions): array
{
    $children = [['text' => 'Hello ']];
    foreach ($mentions as $mention) {
        $children[] = $mention;
        $children[] = ['text' => ' '];
    }

    return [
        ['type' => 'paragraph', 'children' => $children],
    ];
}

// --- extractMentionedUserIds ---

test('extractMentionedUserIds finds mention nodes in document', function () {
    $service = new MentionService;

    $document = documentWithMentions([
        mentionNode(1, 'alice'),
        mentionNode(2, 'bob'),
    ]);

    $result = $service->extractMentionedUserIds($document);

    expect($result)->toBe([1, 2]);
});

test('extractMentionedUserIds handles nested content', function () {
    $service = new MentionService;

    $document = [
        [
            'type' => 'blockquote',
            'children' => [
                [
                    'type' => 'paragraph',
                    'children' => [
                        ['text' => 'Quoting '],
                        mentionNode(5, 'nested'),
                    ],
                ],
            ],
        ],
    ];

    $result = $service->extractMentionedUserIds($document);

    expect($result)->toBe([5]);
});

test('extractMentionedUserIds deduplicates user IDs', function () {
    $service = new MentionService;

    $document = documentWithMentions([
        mentionNode(3, 'repeated'),
        mentionNode(3, 'repeated'),
    ]);

    $result = $service->extractMentionedUserIds($document);

    expect($result)->toBe([3]);
});

test('extractMentionedUserIds returns empty array for document without mentions', function () {
    $service = new MentionService;

    $document = [
        ['type' => 'paragraph', 'children' => [['text' => 'No mentions here.']]],
    ];

    $result = $service->extractMentionedUserIds($document);

    expect($result)->toBe([]);
});

// --- notifyMentionedUsers ---

test('notifyMentionedUsers sends MentionNotification to mentioned users', function () {
    Notification::fake();

    $author = User::factory()->create();
    $mentioned = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
    ]);

    $document = documentWithMentions([
        mentionNode($mentioned->id, $mentioned->username),
    ]);

    $service = new MentionService;
    $service->notifyMentionedUsers($document, $author, $discussion);

    Notification::assertSentTo($mentioned, MentionNotification::class);
});

test('notifyMentionedUsers excludes the author', function () {
    Notification::fake();

    $author = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
    ]);

    $document = documentWithMentions([
        mentionNode($author->id, $author->username),
    ]);

    $service = new MentionService;
    $service->notifyMentionedUsers($document, $author, $discussion);

    Notification::assertNotSentTo($author, MentionNotification::class);
});

test('notifyMentionedUsers excludes deleted users', function () {
    Notification::fake();

    $author = User::factory()->create();
    $deleted = User::factory()->deleted()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
    ]);

    $document = documentWithMentions([
        mentionNode($deleted->id, $deleted->username),
    ]);

    $service = new MentionService;
    $service->notifyMentionedUsers($document, $author, $discussion);

    Notification::assertNotSentTo($deleted, MentionNotification::class);
});
