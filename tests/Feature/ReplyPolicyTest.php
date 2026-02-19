<?php

use App\Models\Discussion;
use App\Models\Reply;
use App\Models\User;
use App\Policies\ReplyPolicy;

// --- create ---

test('create allows any authenticated user for unlocked discussion', function () {
    $user = User::factory()->create();
    $discussion = Discussion::factory()->create();

    expect((new ReplyPolicy)->create($user, $discussion))->toBeTrue();
});

test('create denies any user for locked discussion', function () {
    $user = User::factory()->create();
    $discussion = Discussion::factory()->locked()->create();

    expect((new ReplyPolicy)->create($user, $discussion))->toBeFalse();
});

test('create denies admin for locked discussion', function () {
    $admin = User::factory()->admin()->create();
    $discussion = Discussion::factory()->locked()->create();

    expect((new ReplyPolicy)->create($admin, $discussion))->toBeFalse();
});

test('create denies moderator for locked discussion', function () {
    $moderator = User::factory()->moderator()->create();
    $discussion = Discussion::factory()->locked()->create();

    expect((new ReplyPolicy)->create($moderator, $discussion))->toBeFalse();
});

// --- update ---

test('update allows the reply owner', function () {
    $user = User::factory()->create();
    $reply = Reply::factory()->create(['user_id' => $user->id]);

    expect((new ReplyPolicy)->update($user, $reply))->toBeTrue();
});

test('update denies a different regular user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $reply = Reply::factory()->create(['user_id' => $owner->id]);

    expect((new ReplyPolicy)->update($other, $reply))->toBeFalse();
});

test('update allows admin regardless of ownership', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $reply = Reply::factory()->create(['user_id' => $owner->id]);

    expect((new ReplyPolicy)->update($admin, $reply))->toBeTrue();
});

test('update allows moderator regardless of ownership', function () {
    $moderator = User::factory()->moderator()->create();
    $owner = User::factory()->create();
    $reply = Reply::factory()->create(['user_id' => $owner->id]);

    expect((new ReplyPolicy)->update($moderator, $reply))->toBeTrue();
});

// --- delete ---

test('delete allows the reply owner', function () {
    $user = User::factory()->create();
    $reply = Reply::factory()->create(['user_id' => $user->id]);

    expect((new ReplyPolicy)->delete($user, $reply))->toBeTrue();
});

test('delete denies a different regular user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $reply = Reply::factory()->create(['user_id' => $owner->id]);

    expect((new ReplyPolicy)->delete($other, $reply))->toBeFalse();
});

test('delete allows admin regardless of ownership', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $reply = Reply::factory()->create(['user_id' => $owner->id]);

    expect((new ReplyPolicy)->delete($admin, $reply))->toBeTrue();
});

test('delete allows moderator regardless of ownership', function () {
    $moderator = User::factory()->moderator()->create();
    $owner = User::factory()->create();
    $reply = Reply::factory()->create(['user_id' => $owner->id]);

    expect((new ReplyPolicy)->delete($moderator, $reply))->toBeTrue();
});
