<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('user can upload an avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200)->size(1024),
        ]);

    $response->assertRedirect(route('profile.edit'));
    $user->refresh();
    expect($user->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar_path);
});

test('user can upload a png avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->image('avatar.png', 200, 200)->size(512),
        ]);

    $response->assertRedirect(route('profile.edit'));
    $user->refresh();
    expect($user->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar_path);
});

test('user can upload a webp avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->image('avatar.webp', 200, 200)->size(512),
        ]);

    $response->assertRedirect(route('profile.edit'));
    $user->refresh();
    expect($user->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar_path);
});

test('uploading avatar replaces existing avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    // Upload first avatar
    $this->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->image('first.jpg', 200, 200),
        ]);

    $user->refresh();
    $oldPath = $user->avatar_path;
    expect($oldPath)->not->toBeNull();
    Storage::disk('public')->assertExists($oldPath);

    // Upload second avatar
    $this->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->image('second.jpg', 200, 200),
        ]);

    $user->refresh();
    expect($user->avatar_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($user->avatar_path);
});

test('avatar upload rejects files over 2MB', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->image('large.jpg', 200, 200)->size(2049),
        ]);

    $response->assertSessionHasErrors('avatar');
    expect($user->refresh()->avatar_path)->toBeNull();
});

test('avatar upload rejects non-image files', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]);

    $response->assertSessionHasErrors('avatar');
    expect($user->refresh()->avatar_path)->toBeNull();
});

test('avatar upload rejects invalid image types', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->image('animation.gif', 200, 200),
        ]);

    $response->assertSessionHasErrors('avatar');
    expect($user->refresh()->avatar_path)->toBeNull();
});

test('user can delete their avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    // Upload an avatar first
    $this->actingAs($user)
        ->post(route('avatar.store'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
        ]);

    $user->refresh();
    $avatarPath = $user->avatar_path;
    expect($avatarPath)->not->toBeNull();

    // Delete the avatar
    $response = $this
        ->actingAs($user)
        ->delete(route('avatar.destroy'));

    $response->assertRedirect(route('profile.edit'));
    expect($user->refresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($avatarPath);
});

test('deleting avatar when none exists does not error', function () {
    $user = User::factory()->create(['avatar_path' => null]);

    $response = $this
        ->actingAs($user)
        ->delete(route('avatar.destroy'));

    $response->assertRedirect(route('profile.edit'));
    expect($user->refresh()->avatar_path)->toBeNull();
});

test('guest cannot upload avatar', function () {
    Storage::fake('public');

    $response = $this->post(route('avatar.store'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
    ]);

    $response->assertRedirect(route('login'));
});

test('guest cannot delete avatar', function () {
    $response = $this->delete(route('avatar.destroy'));

    $response->assertRedirect(route('login'));
});
