<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('authenticated user can upload an avatar', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar_path);
});

test('avatar must be a valid image', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]);

    $response->assertSessionHasErrors('avatar');
});

test('avatar must not exceed 2MB', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->image('large.jpg')->size(3000),
        ]);

    $response->assertSessionHasErrors('avatar');
});

test('avatar must be jpg, png, or webp', function (string $extension, bool $valid) {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->image("avatar.{$extension}"),
        ]);

    if ($valid) {
        $response->assertSessionHasNoErrors();
    } else {
        $response->assertSessionHasErrors('avatar');
    }
})->with([
    'jpg' => ['jpg', true],
    'jpeg' => ['jpeg', true],
    'png' => ['png', true],
    'webp' => ['webp', true],
    'gif' => ['gif', false],
    'svg' => ['svg', false],
]);

test('uploading new avatar deletes the old one', function () {
    $user = User::factory()->create();

    // Upload first avatar
    $this->actingAs($user)->post(route('avatar.store'), [
        'avatar' => UploadedFile::fake()->image('first.jpg'),
    ]);

    $oldPath = $user->refresh()->avatar_path;
    Storage::disk('public')->assertExists($oldPath);

    // Upload second avatar
    $this->actingAs($user)->post(route('avatar.store'), [
        'avatar' => UploadedFile::fake()->image('second.jpg'),
    ]);

    $newPath = $user->refresh()->avatar_path;

    expect($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($newPath);
});

test('authenticated user can remove their avatar', function () {
    $user = User::factory()->create();

    // Upload avatar first
    $this->actingAs($user)->post(route('avatar.store'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $avatarPath = $user->refresh()->avatar_path;
    Storage::disk('public')->assertExists($avatarPath);

    // Remove avatar
    $response = $this
        ->actingAs($user)
        ->delete(route('avatar.destroy'));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($avatarPath);
});

test('removing avatar when none exists does not error', function () {
    $user = User::factory()->create(['avatar_path' => null]);

    $response = $this
        ->actingAs($user)
        ->delete(route('avatar.destroy'));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));
});

test('unauthenticated user cannot upload avatar', function () {
    $response = $this->post(route('avatar.store'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $response->assertRedirect(route('login'));
});

test('avatar is required for upload', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('avatar.store'), []);

    $response->assertSessionHasErrors('avatar');
});

test('unverified user can upload an avatar', function () {
    $user = User::factory()->unverified()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->avatar_path)->not->toBeNull();
});

test('unverified user can remove their avatar', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('avatar.store'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $avatarPath = $user->refresh()->avatar_path;

    $response = $this
        ->actingAs($user)
        ->delete(route('avatar.destroy'));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->avatar_path)->toBeNull();
});
