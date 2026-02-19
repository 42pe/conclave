<?php

use App\Enums\TopicVisibility;
use App\Models\Topic;
use App\Models\User;

test('topic belongs to creator', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create(['created_by' => $user->id]);

    expect($topic->creator)->toBeInstanceOf(User::class);
    expect($topic->creator->id)->toBe($user->id);
});

test('topic generates slug from title on creation', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create([
        'title' => 'My Awesome Topic',
        'created_by' => $user->id,
    ]);

    expect($topic->slug)->toBe('my-awesome-topic');
});

test('topic generates unique slug when duplicate title exists', function () {
    $user = User::factory()->create();
    $topic1 = Topic::factory()->create([
        'title' => 'Duplicate Title',
        'created_by' => $user->id,
    ]);
    $topic2 = Topic::factory()->create([
        'title' => 'Duplicate Title',
        'created_by' => $user->id,
    ]);

    expect($topic1->slug)->toBe('duplicate-title');
    expect($topic2->slug)->not->toBe('duplicate-title');
    expect($topic2->slug)->toStartWith('duplicate-title');
});

test('topic visibility cast works correctly', function () {
    $user = User::factory()->create();

    $public = Topic::factory()->create(['visibility' => TopicVisibility::Public, 'created_by' => $user->id]);
    $private = Topic::factory()->create(['visibility' => TopicVisibility::Private, 'created_by' => $user->id]);
    $restricted = Topic::factory()->create(['visibility' => TopicVisibility::Restricted, 'created_by' => $user->id]);

    expect($public->visibility)->toBe(TopicVisibility::Public);
    expect($private->visibility)->toBe(TopicVisibility::Private);
    expect($restricted->visibility)->toBe(TopicVisibility::Restricted);
});

test('topic factory creates valid topic with default state', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create(['created_by' => $user->id]);

    expect($topic->title)->toBeString();
    expect($topic->slug)->toBeString();
    expect($topic->visibility)->toBeInstanceOf(TopicVisibility::class);
    expect($topic->created_by)->toBe($user->id);
});

test('topic factory public state sets public visibility', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->public()->create(['created_by' => $user->id]);

    expect($topic->visibility)->toBe(TopicVisibility::Public);
});

test('topic factory private state sets private visibility', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->private()->create(['created_by' => $user->id]);

    expect($topic->visibility)->toBe(TopicVisibility::Private);
});

test('topic factory restricted state sets restricted visibility', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->restricted()->create(['created_by' => $user->id]);

    expect($topic->visibility)->toBe(TopicVisibility::Restricted);
});
