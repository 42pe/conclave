<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// === Listing ===

it('shows the directory page', function () {
    User::factory()->count(3)->create();

    $response = $this->get('/directory');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('directory/index')
        ->has('users.data', 3)
    );
});

it('excludes deleted users from directory', function () {
    User::factory()->count(2)->create();
    User::factory()->deleted()->create();

    $response = $this->get('/directory');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('users.data', 2)
    );
});

it('excludes users who opted out of directory', function () {
    User::factory()->count(2)->create();
    User::factory()->create(['show_in_directory' => false]);

    $response = $this->get('/directory');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('users.data', 2)
    );
});

// === Search ===

it('searches users by username', function () {
    User::factory()->create(['username' => 'john_doe']);
    User::factory()->create(['username' => 'jane_smith']);

    $response = $this->get('/directory?search=john');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('users.data', 1)
        ->where('search', 'john')
    );
});

it('searches users by name', function () {
    User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
    User::factory()->create(['name' => 'Jane Smith', 'username' => 'janesmith']);

    $response = $this->get('/directory?search=John');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('users.data', 1)
    );
});

it('searches users by preferred name', function () {
    User::factory()->create(['preferred_name' => 'Johnny', 'username' => 'johndoe']);
    User::factory()->create(['preferred_name' => null, 'username' => 'janesmith']);

    $response = $this->get('/directory?search=Johnny');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('users.data', 1)
    );
});

it('returns empty results for no matches', function () {
    User::factory()->count(3)->create();

    $response = $this->get('/directory?search=nonexistent_xyz');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('users.data', 0)
    );
});

// === Pagination ===

it('paginates directory results', function () {
    User::factory()->count(30)->create();

    $response = $this->get('/directory');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('users.data', 24)
    );
});

// === Ordering ===

it('orders users alphabetically by username', function () {
    User::factory()->create(['username' => 'zephyr']);
    User::factory()->create(['username' => 'alpha']);
    User::factory()->create(['username' => 'charlie']);

    $response = $this->get('/directory');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('users.data', 3)
        ->where('users.data.0.username', 'alpha')
        ->where('users.data.1.username', 'charlie')
        ->where('users.data.2.username', 'zephyr')
    );
});
