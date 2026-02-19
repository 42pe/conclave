<?php

use App\Enums\UserRole;
use App\Models\User;

test('display name returns preferred name when set', function () {
    $user = new User([
        'name' => 'John Doe',
        'preferred_name' => 'Johnny',
        'is_deleted' => false,
    ]);

    expect($user->display_name)->toBe('Johnny');
});

test('display name falls back to name when preferred name is not set', function () {
    $user = new User([
        'name' => 'John Doe',
        'preferred_name' => null,
        'is_deleted' => false,
    ]);

    expect($user->display_name)->toBe('John Doe');
});

test('display name returns deleted user for deleted accounts', function () {
    $user = new User([
        'name' => 'John Doe',
        'preferred_name' => 'Johnny',
        'is_deleted' => true,
    ]);

    expect($user->display_name)->toBe('Deleted User');
});

test('isAdmin returns true for admin role', function () {
    $user = new User(['role' => UserRole::Admin]);

    expect($user->isAdmin())->toBeTrue();
    expect($user->isModerator())->toBeFalse();
    expect($user->isAdminOrModerator())->toBeTrue();
});

test('isModerator returns true for moderator role', function () {
    $user = new User(['role' => UserRole::Moderator]);

    expect($user->isModerator())->toBeTrue();
    expect($user->isAdmin())->toBeFalse();
    expect($user->isAdminOrModerator())->toBeTrue();
});

test('regular user is not admin or moderator', function () {
    $user = new User(['role' => UserRole::User]);

    expect($user->isAdmin())->toBeFalse();
    expect($user->isModerator())->toBeFalse();
    expect($user->isAdminOrModerator())->toBeFalse();
});

test('user role is cast to UserRole enum', function () {
    $user = new User(['role' => 'admin']);

    expect($user->role)->toBe(UserRole::Admin);
});
