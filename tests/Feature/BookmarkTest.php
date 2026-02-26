<?php

use App\Models\Bookmark;
use App\Models\Discussion;
use App\Models\Topic;
use App\Models\User;
use App\Services\PostHogService;

test('user can bookmark a discussion', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this->actingAs($user)
        ->postJson(route('discussions.bookmark', $discussion));

    $response->assertOk()->assertJson(['bookmarked' => true]);
    expect(Bookmark::where('user_id', $user->id)->where('discussion_id', $discussion->id)->exists())->toBeTrue();
});

test('user can remove bookmark', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    Bookmark::create(['user_id' => $user->id, 'discussion_id' => $discussion->id]);

    $response = $this->actingAs($user)
        ->postJson(route('discussions.bookmark', $discussion));

    $response->assertOk()->assertJson(['bookmarked' => false]);
    expect(Bookmark::where('user_id', $user->id)->where('discussion_id', $discussion->id)->exists())->toBeFalse();
});

test('toggle behavior - no duplicates', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    // Bookmark
    $this->actingAs($user)->postJson(route('discussions.bookmark', $discussion));
    expect(Bookmark::where('user_id', $user->id)->where('discussion_id', $discussion->id)->count())->toBe(1);

    // Unbookmark
    $this->actingAs($user)->postJson(route('discussions.bookmark', $discussion));
    expect(Bookmark::where('user_id', $user->id)->where('discussion_id', $discussion->id)->count())->toBe(0);

    // Bookmark again
    $this->actingAs($user)->postJson(route('discussions.bookmark', $discussion));
    expect(Bookmark::where('user_id', $user->id)->where('discussion_id', $discussion->id)->count())->toBe(1);
});

test('auth required to bookmark', function () {
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $this->postJson(route('discussions.bookmark', $discussion))->assertUnauthorized();
});

test('bookmarks index shows bookmarked discussions', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    Bookmark::create(['user_id' => $user->id, 'discussion_id' => $discussion->id]);

    $response = $this->actingAs($user)->get(route('bookmarks.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('bookmarks/index')
        ->has('bookmarks.data', 1)
        ->where('bookmarks.data.0.discussion_id', $discussion->id)
    );
});

test('bookmarks index only shows own bookmarks', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion1 = Discussion::factory()->create(['topic_id' => $topic->id]);
    $discussion2 = Discussion::factory()->create(['topic_id' => $topic->id]);

    Bookmark::create(['user_id' => $user->id, 'discussion_id' => $discussion1->id]);
    Bookmark::create(['user_id' => $otherUser->id, 'discussion_id' => $discussion2->id]);

    $response = $this->actingAs($user)->get(route('bookmarks.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('bookmarks/index')
        ->has('bookmarks.data', 1)
        ->where('bookmarks.data.0.discussion_id', $discussion1->id)
    );
});

test('topic listing includes bookmark data for discussions', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    Bookmark::create(['user_id' => $user->id, 'discussion_id' => $discussion->id]);

    $response = $this->actingAs($user)->get(route('topics.show', $topic));

    $response->assertInertia(fn ($page) => $page
        ->component('topics/show')
        ->where('discussions.data.0.user_has_bookmarked', true)
    );
});

test('topic listing shows false for user_has_bookmarked when not bookmarked', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this->actingAs($user)->get(route('topics.show', $topic));

    $response->assertInertia(fn ($page) => $page
        ->component('topics/show')
        ->where('discussions.data.0.user_has_bookmarked', false)
    );
});

test('discussion show page includes user_has_bookmarked', function () {
    $this->mock(PostHogService::class)->shouldReceive('capture');
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);
    Bookmark::create(['user_id' => $user->id, 'discussion_id' => $discussion->id]);

    $response = $this->actingAs($user)
        ->get(route('topics.discussions.show', [$topic, $discussion]));

    $response->assertInertia(fn ($page) => $page
        ->component('discussions/show')
        ->where('discussion.user_has_bookmarked', true)
    );
});

test('discussion show page shows false for user_has_bookmarked when not bookmarked', function () {
    $this->mock(PostHogService::class)->shouldReceive('capture');
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $response = $this->actingAs($user)
        ->get(route('topics.discussions.show', [$topic, $discussion]));

    $response->assertInertia(fn ($page) => $page
        ->component('discussions/show')
        ->where('discussion.user_has_bookmarked', false)
    );
});
