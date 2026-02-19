<?php

use App\Models\User;

// --- Directory page visibility ---

test('anyone can view the directory page', function () {
    $response = $this->get(route('directory.index'));

    $response->assertOk();
});

test('directory page renders the correct Inertia component', function () {
    $response = $this->get(route('directory.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
    );
});

test('authenticated user can view the directory page', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('directory.index'));

    $response->assertOk();
});

// --- Directory lists active users ---

test('directory lists active users', function () {
    User::factory()->count(3)->create([
        'show_in_directory' => true,
    ]);

    $response = $this->get(route('directory.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->has('users.data', 3)
    );
});

test('directory paginates users', function () {
    User::factory()->count(30)->create([
        'show_in_directory' => true,
    ]);

    $response = $this->get(route('directory.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->has('users.data', 24)
    );
});

// --- Directory exclusions ---

test('directory excludes deleted users', function () {
    User::factory()->create(['show_in_directory' => true]);
    User::factory()->deleted()->create(['show_in_directory' => true]);

    $response = $this->get(route('directory.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->has('users.data', 1)
    );
});

test('directory excludes users with show_in_directory false', function () {
    User::factory()->create(['show_in_directory' => true]);
    User::factory()->create(['show_in_directory' => false]);

    $response = $this->get(route('directory.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->has('users.data', 1)
    );
});

test('directory excludes both deleted and hidden users', function () {
    User::factory()->create(['show_in_directory' => true]);
    User::factory()->deleted()->create(['show_in_directory' => true]);
    User::factory()->create(['show_in_directory' => false]);

    $response = $this->get(route('directory.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->has('users.data', 1)
    );
});

// --- Directory search ---

test('directory search by username works', function () {
    User::factory()->create([
        'username' => 'janesmith01',
        'show_in_directory' => true,
    ]);
    User::factory()->create([
        'username' => 'bobdoe02',
        'show_in_directory' => true,
    ]);

    $response = $this->get(route('directory.index', ['search' => 'janesmith']));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->has('users.data', 1)
        ->where('users.data.0.username', 'janesmith01')
    );
});

test('directory search by name works', function () {
    User::factory()->create([
        'name' => 'Jane Smith',
        'username' => 'user001',
        'show_in_directory' => true,
    ]);
    User::factory()->create([
        'name' => 'Bob Doe',
        'username' => 'user002',
        'show_in_directory' => true,
    ]);

    $response = $this->get(route('directory.index', ['search' => 'Jane']));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->has('users.data', 1)
        ->where('users.data.0.name', 'Jane Smith')
    );
});

test('directory search by preferred_name works', function () {
    User::factory()->create([
        'preferred_name' => 'Janey',
        'username' => 'user001',
        'show_in_directory' => true,
    ]);
    User::factory()->create([
        'preferred_name' => null,
        'username' => 'user002',
        'show_in_directory' => true,
    ]);

    $response = $this->get(route('directory.index', ['search' => 'Janey']));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->has('users.data', 1)
        ->where('users.data.0.preferred_name', 'Janey')
    );
});

test('directory search returns no results for non-matching query', function () {
    User::factory()->create(['show_in_directory' => true]);

    $response = $this->get(route('directory.index', ['search' => 'zzzznonexistent']));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->has('users.data', 0)
    );
});

test('directory search excludes deleted users from results', function () {
    User::factory()->create([
        'username' => 'janesmith01',
        'show_in_directory' => true,
    ]);
    User::factory()->deleted()->create([
        'username' => 'janesmith02',
        'show_in_directory' => true,
    ]);

    $response = $this->get(route('directory.index', ['search' => 'janesmith']));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->has('users.data', 1)
        ->where('users.data.0.username', 'janesmith01')
    );
});

// --- Directory shows user data ---

test('directory shows user card data', function () {
    $user = User::factory()->create([
        'name' => 'Jane Smith',
        'username' => 'janesmith01',
        'preferred_name' => 'Janey',
        'avatar_path' => 'avatars/test.jpg',
        'bio' => 'Hello world',
        'show_in_directory' => true,
    ]);

    $response = $this->get(route('directory.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->has('users.data', 1)
        ->where('users.data.0.username', 'janesmith01')
        ->where('users.data.0.name', 'Jane Smith')
        ->where('users.data.0.preferred_name', 'Janey')
        ->where('users.data.0.avatar_path', 'avatars/test.jpg')
    );
});

// --- Directory filters ---

test('directory provides filter data', function () {
    $response = $this->get(route('directory.index', ['search' => 'test', 'sort' => 'newest']));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->where('filters.search', 'test')
        ->where('filters.sort', 'newest')
    );
});

test('directory defaults to empty search and name sort', function () {
    $response = $this->get(route('directory.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->where('filters.search', '')
        ->where('filters.sort', 'name')
    );
});

// --- Unverified users ---

test('unverified users appear in directory', function () {
    User::factory()->unverified()->create(['show_in_directory' => true]);
    User::factory()->create(['show_in_directory' => true]);

    $response = $this->get(route('directory.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->has('users.data', 2)
    );
});

// --- Suspended users ---

test('suspended users still appear in directory', function () {
    User::factory()->suspended()->create(['show_in_directory' => true]);
    User::factory()->create(['show_in_directory' => true]);

    $response = $this->get(route('directory.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('directory/index', false)
        ->has('users.data', 2)
    );
});
