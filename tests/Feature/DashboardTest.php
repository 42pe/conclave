<?php

use App\Models\Discussion;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;
use Inertia\Inertia;

// --- Authentication ---

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

// --- Inertia component ---

test('dashboard renders the correct inertia component', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard', false)
    );
});

// --- userStats (immediate prop) ---

test('dashboard returns userStats with discussions count and replies count', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    Discussion::factory()->count(3)->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    Reply::factory()->count(2)->create([
        'user_id' => $user->id,
        'discussion_id' => Discussion::factory()->create(['topic_id' => $topic->id])->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard', false)
        ->has('userStats')
        ->where('userStats.discussions_count', 3)
        ->where('userStats.replies_count', 2)
    );
});

test('dashboard userStats counts only the authenticated users data', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $topic = Topic::factory()->public()->create();

    Discussion::factory()->count(2)->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    Discussion::factory()->count(5)->create([
        'topic_id' => $topic->id,
        'user_id' => $otherUser->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('userStats.discussions_count', 2)
        ->where('userStats.replies_count', 0)
    );
});

test('dashboard userStats shows zero counts for new user', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('userStats.discussions_count', 0)
        ->where('userStats.replies_count', 0)
    );
});

// --- Deferred props are NOT included in initial page load ---

test('dashboard does not include deferred props in initial page load', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('userStats')
        ->missing('recentReplies')
        ->missing('activeTopics')
        ->missing('recentDiscussions')
    );
});

// --- recentReplies (deferred prop via partial request) ---

test('dashboard recentReplies contains replies to user discussions by other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    Reply::factory()->count(3)->create([
        'discussion_id' => $discussion->id,
        'user_id' => $otherUser->id,
    ]);

    // First GET to trigger middleware so Inertia version is set
    $this->actingAs($user)->get(route('dashboard'));

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Data' => 'recentReplies',
            'X-Inertia-Partial-Component' => 'dashboard',
        ]);

    $response->assertOk();
    $data = $response->json('props.recentReplies');
    expect($data)->toHaveCount(3);
});

test('dashboard recentReplies excludes user own replies to their discussions', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    // User replies to their own discussion - should be excluded
    Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
    ]);

    // Other user replies - should be included
    Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Data' => 'recentReplies',
            'X-Inertia-Partial-Component' => 'dashboard',
        ]);

    $response->assertOk();
    $data = $response->json('props.recentReplies');
    expect($data)->toHaveCount(1);
    expect($data[0]['user']['id'])->toBe($otherUser->id);
});

// --- activeTopics (deferred prop via partial request) ---

test('dashboard activeTopics excludes restricted topics for regular users', function () {
    $user = User::factory()->create();
    $publicTopic = Topic::factory()->public()->create();
    $restrictedTopic = Topic::factory()->restricted()->create();

    Discussion::factory()->create(['topic_id' => $publicTopic->id]);
    Discussion::factory()->create(['topic_id' => $restrictedTopic->id]);

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Data' => 'activeTopics',
            'X-Inertia-Partial-Component' => 'dashboard',
        ]);

    $response->assertOk();
    $data = $response->json('props.activeTopics');
    $topicIds = collect($data)->pluck('id')->all();
    expect($topicIds)->toContain($publicTopic->id);
    expect($topicIds)->not->toContain($restrictedTopic->id);
});

test('dashboard activeTopics includes restricted topics for admin users', function () {
    $admin = User::factory()->admin()->create();
    $publicTopic = Topic::factory()->public()->create();
    $restrictedTopic = Topic::factory()->restricted()->create();

    Discussion::factory()->create(['topic_id' => $publicTopic->id]);
    Discussion::factory()->create(['topic_id' => $restrictedTopic->id]);

    $this->actingAs($admin)->get(route('dashboard'));

    $response = $this
        ->actingAs($admin)
        ->get(route('dashboard'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Data' => 'activeTopics',
            'X-Inertia-Partial-Component' => 'dashboard',
        ]);

    $response->assertOk();
    $data = $response->json('props.activeTopics');
    $topicIds = collect($data)->pluck('id')->all();
    expect($topicIds)->toContain($publicTopic->id);
    expect($topicIds)->toContain($restrictedTopic->id);
});

// --- recentDiscussions (deferred prop via partial request) ---

test('dashboard recentDiscussions excludes discussions in restricted topics for regular users', function () {
    $user = User::factory()->create();
    $publicTopic = Topic::factory()->public()->create();
    $restrictedTopic = Topic::factory()->restricted()->create();

    $publicDiscussion = Discussion::factory()->create(['topic_id' => $publicTopic->id]);
    $restrictedDiscussion = Discussion::factory()->create(['topic_id' => $restrictedTopic->id]);

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Data' => 'recentDiscussions',
            'X-Inertia-Partial-Component' => 'dashboard',
        ]);

    $response->assertOk();
    $data = $response->json('props.recentDiscussions');
    $discussionIds = collect($data)->pluck('id')->all();
    expect($discussionIds)->toContain($publicDiscussion->id);
    expect($discussionIds)->not->toContain($restrictedDiscussion->id);
});

test('dashboard recentDiscussions includes discussions in restricted topics for admin users', function () {
    $admin = User::factory()->admin()->create();
    $publicTopic = Topic::factory()->public()->create();
    $restrictedTopic = Topic::factory()->restricted()->create();

    $publicDiscussion = Discussion::factory()->create(['topic_id' => $publicTopic->id]);
    $restrictedDiscussion = Discussion::factory()->create(['topic_id' => $restrictedTopic->id]);

    $this->actingAs($admin)->get(route('dashboard'));

    $response = $this
        ->actingAs($admin)
        ->get(route('dashboard'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Data' => 'recentDiscussions',
            'X-Inertia-Partial-Component' => 'dashboard',
        ]);

    $response->assertOk();
    $data = $response->json('props.recentDiscussions');
    $discussionIds = collect($data)->pluck('id')->all();
    expect($discussionIds)->toContain($publicDiscussion->id);
    expect($discussionIds)->toContain($restrictedDiscussion->id);
});

// --- recentReplies limit ---

test('dashboard recentReplies limits to 5 results', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    Reply::factory()->count(8)->create([
        'discussion_id' => $discussion->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Data' => 'recentReplies',
            'X-Inertia-Partial-Component' => 'dashboard',
        ]);

    $response->assertOk();
    $data = $response->json('props.recentReplies');
    expect($data)->toHaveCount(5);
});

// --- recentReplies relationships ---

test('dashboard recentReplies includes user, discussion, and topic relationships', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    $discussion = Discussion::factory()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
    ]);

    Reply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Data' => 'recentReplies',
            'X-Inertia-Partial-Component' => 'dashboard',
        ]);

    $response->assertOk();
    $data = $response->json('props.recentReplies');
    expect($data)->toHaveCount(1);
    expect($data[0])->toHaveKeys(['user', 'discussion']);
    expect($data[0]['discussion'])->toHaveKeys(['id', 'title', 'slug', 'topic_id']);
    expect($data[0]['discussion']['topic'])->toHaveKeys(['id', 'title', 'slug', 'icon']);
});

// --- activeTopics limits ---

test('dashboard activeTopics limits to 5 results', function () {
    $user = User::factory()->create();

    $topics = Topic::factory()->count(7)->public()->create();
    foreach ($topics as $topic) {
        Discussion::factory()->create(['topic_id' => $topic->id]);
    }

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Data' => 'activeTopics',
            'X-Inertia-Partial-Component' => 'dashboard',
        ]);

    $response->assertOk();
    $data = $response->json('props.activeTopics');
    expect($data)->toHaveCount(5);
});

// --- activeTopics includes discussion count ---

test('dashboard activeTopics includes discussions_count', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    Discussion::factory()->count(4)->create(['topic_id' => $topic->id]);

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Data' => 'activeTopics',
            'X-Inertia-Partial-Component' => 'dashboard',
        ]);

    $response->assertOk();
    $data = $response->json('props.activeTopics');
    expect($data)->toHaveCount(1);
    expect($data[0]['discussions_count'])->toBe(4);
    expect($data[0])->toHaveKeys(['id', 'title', 'slug', 'icon']);
});

// --- recentDiscussions limits ---

test('dashboard recentDiscussions limits to 5 results', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    Discussion::factory()->count(8)->create(['topic_id' => $topic->id]);

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Data' => 'recentDiscussions',
            'X-Inertia-Partial-Component' => 'dashboard',
        ]);

    $response->assertOk();
    $data = $response->json('props.recentDiscussions');
    expect($data)->toHaveCount(5);
});

// --- recentDiscussions includes private topics ---

test('dashboard recentDiscussions includes private topic discussions for authenticated users', function () {
    $user = User::factory()->create();
    $privateTopic = Topic::factory()->private()->create();
    $privateDiscussion = Discussion::factory()->create(['topic_id' => $privateTopic->id]);

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Data' => 'recentDiscussions',
            'X-Inertia-Partial-Component' => 'dashboard',
        ]);

    $response->assertOk();
    $data = $response->json('props.recentDiscussions');
    $discussionIds = collect($data)->pluck('id')->all();
    expect($discussionIds)->toContain($privateDiscussion->id);
});

// --- recentDiscussions includes relationships ---

test('dashboard recentDiscussions includes user and topic relationships', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create();
    Discussion::factory()->create(['topic_id' => $topic->id]);

    $this->actingAs($user)->get(route('dashboard'));

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Inertia-Partial-Data' => 'recentDiscussions',
            'X-Inertia-Partial-Component' => 'dashboard',
        ]);

    $response->assertOk();
    $data = $response->json('props.recentDiscussions');
    expect($data)->toHaveCount(1);
    expect($data[0])->toHaveKeys(['user', 'topic']);
    expect($data[0]['topic'])->toHaveKeys(['id', 'title', 'slug', 'icon']);
});
