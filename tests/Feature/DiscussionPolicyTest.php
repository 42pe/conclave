<?php

use App\Models\Discussion;
use App\Models\Topic;
use App\Models\User;

// --- viewAny ---

test('viewAny allows anyone for public topics', function () {
    $topic = Topic::factory()->public()->create();

    expect((new \App\Policies\DiscussionPolicy)->viewAny(null, $topic))->toBeTrue();
});

test('viewAny allows authenticated user for private topics', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->private()->create();

    expect((new \App\Policies\DiscussionPolicy)->viewAny($user, $topic))->toBeTrue();
});

test('viewAny denies guest for private topics', function () {
    $topic = Topic::factory()->private()->create();

    expect((new \App\Policies\DiscussionPolicy)->viewAny(null, $topic))->toBeFalse();
});

test('viewAny allows admin for restricted topics', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->restricted()->create();

    expect((new \App\Policies\DiscussionPolicy)->viewAny($admin, $topic))->toBeTrue();
});

test('viewAny allows moderator for restricted topics', function () {
    $moderator = User::factory()->moderator()->create();
    $topic = Topic::factory()->restricted()->create();

    expect((new \App\Policies\DiscussionPolicy)->viewAny($moderator, $topic))->toBeTrue();
});

test('viewAny denies regular user for restricted topics', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->restricted()->create();

    expect((new \App\Policies\DiscussionPolicy)->viewAny($user, $topic))->toBeFalse();
});

test('viewAny denies guest for restricted topics', function () {
    $topic = Topic::factory()->restricted()->create();

    expect((new \App\Policies\DiscussionPolicy)->viewAny(null, $topic))->toBeFalse();
});

// --- view ---

test('view allows anyone for discussion in public topic', function () {
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    expect((new \App\Policies\DiscussionPolicy)->view(null, $discussion))->toBeTrue();
});

test('view denies guest for discussion in private topic', function () {
    $topic = Topic::factory()->private()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    expect((new \App\Policies\DiscussionPolicy)->view(null, $discussion))->toBeFalse();
});

test('view allows authenticated user for discussion in private topic', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->private()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    expect((new \App\Policies\DiscussionPolicy)->view($user, $discussion))->toBeTrue();
});

test('view denies regular user for discussion in restricted topic', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->restricted()->create();
    $discussion = Discussion::factory()->create(['topic_id' => $topic->id]);

    expect((new \App\Policies\DiscussionPolicy)->view($user, $discussion))->toBeFalse();
});

// --- create ---

test('create allows any authenticated user for public topic', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    expect((new \App\Policies\DiscussionPolicy)->create($user, $topic))->toBeTrue();
});

test('create allows any authenticated user for private topic', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->private()->create();

    expect((new \App\Policies\DiscussionPolicy)->create($user, $topic))->toBeTrue();
});

test('create denies regular user for restricted topic', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->restricted()->create();

    expect((new \App\Policies\DiscussionPolicy)->create($user, $topic))->toBeFalse();
});

test('create allows admin for restricted topic', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->restricted()->create();

    expect((new \App\Policies\DiscussionPolicy)->create($admin, $topic))->toBeTrue();
});

test('create allows moderator for restricted topic', function () {
    $moderator = User::factory()->moderator()->create();
    $topic = Topic::factory()->restricted()->create();

    expect((new \App\Policies\DiscussionPolicy)->create($moderator, $topic))->toBeTrue();
});

// --- update ---

test('update allows the discussion owner', function () {
    $user = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $user->id]);

    expect((new \App\Policies\DiscussionPolicy)->update($user, $discussion))->toBeTrue();
});

test('update denies a different regular user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $owner->id]);

    expect((new \App\Policies\DiscussionPolicy)->update($other, $discussion))->toBeFalse();
});

test('update allows admin regardless of ownership', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $owner->id]);

    expect((new \App\Policies\DiscussionPolicy)->update($admin, $discussion))->toBeTrue();
});

test('update allows moderator regardless of ownership', function () {
    $moderator = User::factory()->moderator()->create();
    $owner = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $owner->id]);

    expect((new \App\Policies\DiscussionPolicy)->update($moderator, $discussion))->toBeTrue();
});

// --- delete ---

test('delete allows the discussion owner', function () {
    $user = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $user->id]);

    expect((new \App\Policies\DiscussionPolicy)->delete($user, $discussion))->toBeTrue();
});

test('delete denies a different regular user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $owner->id]);

    expect((new \App\Policies\DiscussionPolicy)->delete($other, $discussion))->toBeFalse();
});

test('delete allows admin regardless of ownership', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $owner->id]);

    expect((new \App\Policies\DiscussionPolicy)->delete($admin, $discussion))->toBeTrue();
});

test('delete allows moderator regardless of ownership', function () {
    $moderator = User::factory()->moderator()->create();
    $owner = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $owner->id]);

    expect((new \App\Policies\DiscussionPolicy)->delete($moderator, $discussion))->toBeTrue();
});
