<?php

use App\Enums\UserRole;
use App\Models\User;

test('display name returns name for normal user', function () {
    $user = User::factory()->create(['name' => 'Jane Smith', 'preferred_name' => null]);

    expect($user->display_name)->toBe('Jane Smith');
});

test('display name returns preferred name when set', function () {
    $user = User::factory()->create(['name' => 'Jane Smith', 'preferred_name' => 'Janey']);

    expect($user->display_name)->toBe('Janey');
});

test('display name returns Deleted User for deleted user', function () {
    $user = User::factory()->deleted()->create(['name' => 'Jane Smith', 'preferred_name' => 'Janey']);

    expect($user->display_name)->toBe('Deleted User');
});

test('isAdmin returns true for admin user', function () {
    $user = User::factory()->admin()->create();

    expect($user->isAdmin())->toBeTrue();
    expect($user->isModerator())->toBeFalse();
    expect($user->isAdminOrModerator())->toBeTrue();
});

test('isModerator returns true for moderator user', function () {
    $user = User::factory()->moderator()->create();

    expect($user->isModerator())->toBeTrue();
    expect($user->isAdmin())->toBeFalse();
    expect($user->isAdminOrModerator())->toBeTrue();
});

test('regular user is not admin or moderator', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(UserRole::User);
    expect($user->isAdmin())->toBeFalse();
    expect($user->isModerator())->toBeFalse();
    expect($user->isAdminOrModerator())->toBeFalse();
});

test('factory admin state sets admin role', function () {
    $user = User::factory()->admin()->create();

    expect($user->role)->toBe(UserRole::Admin);
});

test('factory moderator state sets moderator role', function () {
    $user = User::factory()->moderator()->create();

    expect($user->role)->toBe(UserRole::Moderator);
});

test('factory deleted state sets is_deleted and deleted_at', function () {
    $user = User::factory()->deleted()->create();

    expect($user->is_deleted)->toBeTrue();
    expect($user->deleted_at)->not->toBeNull();
});

test('factory suspended state sets is_suspended', function () {
    $user = User::factory()->suspended()->create();

    expect($user->is_suspended)->toBeTrue();
});
