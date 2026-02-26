<?php

use App\Models\Bookmark;
use App\Models\Discussion;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\BookmarkActivityNotification;
use App\Notifications\NewReplyNotification;
use App\Services\PostHogService;
use Illuminate\Support\Facades\Notification;

function bookmarkSlateBody(): array
{
    return [
        ['type' => 'paragraph', 'children' => [['text' => 'Bookmark test reply']]],
    ];
}

test('bookmark notification sent when reply added to bookmarked discussion', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $bookmarker = User::factory()->create();
    $replier = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => User::factory()->create()->id,
    ]);

    Bookmark::create(['user_id' => $bookmarker->id, 'discussion_id' => $discussion->id]);

    $this->actingAs($replier)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => bookmarkSlateBody(),
        ]);

    Notification::assertSentTo($bookmarker, BookmarkActivityNotification::class);
});

test('no self-notification for bookmark activity', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $replier = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => User::factory()->create()->id,
    ]);

    Bookmark::create(['user_id' => $replier->id, 'discussion_id' => $discussion->id]);

    $this->actingAs($replier)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => bookmarkSlateBody(),
        ]);

    Notification::assertNotSentTo($replier, BookmarkActivityNotification::class);
});

test('no duplicate notification with NewReplyNotification', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $discussionAuthor = User::factory()->create();
    $replier = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $discussionAuthor->id,
    ]);

    // Discussion author also bookmarks the discussion
    Bookmark::create(['user_id' => $discussionAuthor->id, 'discussion_id' => $discussion->id]);

    $this->actingAs($replier)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => bookmarkSlateBody(),
        ]);

    // Should receive NewReplyNotification (as discussion author)
    Notification::assertSentTo($discussionAuthor, NewReplyNotification::class);

    // Should NOT receive BookmarkActivityNotification (already notified as discussion author)
    Notification::assertNotSentTo($discussionAuthor, BookmarkActivityNotification::class);
});

test('bookmark notification uses only database channel', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $bookmarker = User::factory()->create();
    $replier = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => User::factory()->create()->id,
    ]);

    Bookmark::create(['user_id' => $bookmarker->id, 'discussion_id' => $discussion->id]);

    $this->actingAs($replier)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => bookmarkSlateBody(),
        ]);

    Notification::assertSentTo(
        $bookmarker,
        BookmarkActivityNotification::class,
        fn ($notification, $channels) => $channels === ['database'],
    );
});

test('deleted user does not receive bookmark notification', function () {
    Notification::fake();
    $this->mock(PostHogService::class)->shouldReceive('capture');

    $deletedUser = User::factory()->create(['is_deleted' => true]);
    $replier = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => User::factory()->create()->id,
    ]);

    Bookmark::create(['user_id' => $deletedUser->id, 'discussion_id' => $discussion->id]);

    $this->actingAs($replier)
        ->post(route('discussions.replies.store', $discussion), [
            'body' => bookmarkSlateBody(),
        ]);

    Notification::assertNotSentTo($deletedUser, BookmarkActivityNotification::class);
});
