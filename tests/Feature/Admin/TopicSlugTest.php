<?php

use App\Models\Topic;
use App\Models\User;

test('slug is auto-generated from title on creation', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create([
        'title' => 'My Great Topic',
        'created_by' => $user->id,
    ]);

    expect($topic->slug)->toBe('my-great-topic');
});

test('slug is unique with counter for duplicates', function () {
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
    expect($topic2->slug)->toBe('duplicate-title-1');
});

test('slug updates when title changes', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create([
        'title' => 'Original Title',
        'created_by' => $user->id,
    ]);

    expect($topic->slug)->toBe('original-title');

    $topic->update(['title' => 'Updated Title']);

    expect($topic->fresh()->slug)->toBe('updated-title');
});
