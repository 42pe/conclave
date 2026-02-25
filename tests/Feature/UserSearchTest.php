<?php

use App\Models\User;

// --- Search results ---

test('search returns users matching query by username', function () {
    $user = User::factory()->create();
    $match = User::factory()->create(['username' => 'johndoe123']);
    User::factory()->create(['username' => 'janesmith456']);

    $response = $this
        ->actingAs($user)
        ->getJson(route('users.search', ['q' => 'johndoe']));

    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonFragment(['username' => 'johndoe123']);
});

test('search returns users matching by name', function () {
    $user = User::factory()->create();
    $match = User::factory()->create(['name' => 'Alice Wonderland']);

    $response = $this
        ->actingAs($user)
        ->getJson(route('users.search', ['q' => 'Alice']));

    $response->assertOk();
    $response->assertJsonFragment(['name' => 'Alice Wonderland']);
});

test('search returns users matching by preferred_name', function () {
    $user = User::factory()->create();
    $match = User::factory()->create(['preferred_name' => 'Ziggy']);

    $response = $this
        ->actingAs($user)
        ->getJson(route('users.search', ['q' => 'Ziggy']));

    $response->assertOk();
    $response->assertJsonFragment(['preferred_name' => 'Ziggy']);
});

// --- Exclusions ---

test('search excludes deleted users', function () {
    $user = User::factory()->create();
    User::factory()->deleted()->create(['username' => 'deleteduser']);

    $response = $this
        ->actingAs($user)
        ->getJson(route('users.search', ['q' => 'deleteduser']));

    $response->assertOk();
    $response->assertJsonCount(0);
});

test('search excludes self', function () {
    $user = User::factory()->create(['username' => 'myselfuser']);

    $response = $this
        ->actingAs($user)
        ->getJson(route('users.search', ['q' => 'myselfuser']));

    $response->assertOk();
    $response->assertJsonCount(0);
});

// --- Constraints ---

test('search requires minimum 2 characters', function () {
    $user = User::factory()->create();
    User::factory()->create(['username' => 'abcdef']);

    $response = $this
        ->actingAs($user)
        ->getJson(route('users.search', ['q' => 'a']));

    $response->assertOk();
    $response->assertJsonCount(0);
});

test('search returns max 10 results', function () {
    $user = User::factory()->create();
    User::factory()->count(15)->create(['name' => 'Searchable Person']);

    $response = $this
        ->actingAs($user)
        ->getJson(route('users.search', ['q' => 'Searchable']));

    $response->assertOk();
    $response->assertJsonCount(10);
});

// --- Authentication ---

test('search requires authentication', function () {
    $response = $this->getJson(route('users.search', ['q' => 'test']));

    $response->assertUnauthorized();
});

// --- Response shape ---

test('search returns expected fields', function () {
    $user = User::factory()->create();
    $match = User::factory()->create([
        'username' => 'targetuser',
        'name' => 'Target User',
        'preferred_name' => 'Tar',
        'avatar_path' => null,
    ]);

    $response = $this
        ->actingAs($user)
        ->getJson(route('users.search', ['q' => 'targetuser']));

    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonStructure([
        ['id', 'name', 'username', 'preferred_name', 'avatar_path', 'display_name'],
    ]);
});
