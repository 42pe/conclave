<?php

use App\Models\Discussion;
use App\Models\Topic;
use App\Services\PostHogService;

test('view count increments when discussion is viewed', function () {
    $this->mock(PostHogService::class)->shouldReceive('capture');
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    expect($discussion->fresh()->view_count)->toBe(0);

    $this->get(route('topics.discussions.show', [$topic, $discussion]));

    expect($discussion->fresh()->view_count)->toBe(1);
});

test('view count increments on each visit', function () {
    $this->mock(PostHogService::class)->shouldReceive('capture');
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    $this->get(route('topics.discussions.show', [$topic, $discussion]));
    $this->get(route('topics.discussions.show', [$topic, $discussion]));

    expect($discussion->fresh()->view_count)->toBe(2);
});

test('view count is included in topic listing', function () {
    $topic = Topic::factory()->public()->create();
    Discussion::factory()->create(['topic_id' => $topic->id, 'view_count' => 42]);

    $response = $this->get(route('topics.show', $topic));

    $response->assertInertia(fn ($page) => $page
        ->where('discussions.data.0.view_count', 42)
    );
});

test('view count is included in discussion show page', function () {
    $this->mock(PostHogService::class)->shouldReceive('capture');
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id, 'view_count' => 10]);

    $response = $this->get(route('topics.discussions.show', [$topic, $discussion]));

    // view_count should be 11 (10 + 1 increment from this visit)
    $response->assertInertia(fn ($page) => $page
        ->where('discussion.view_count', 11)
    );
});
