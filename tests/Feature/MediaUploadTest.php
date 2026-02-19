<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guest cannot upload media', function () {
    $this->postJson(route('media.upload'))->assertUnauthorized();
});

test('authenticated user can upload a jpeg image', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ]);

    $response->assertCreated()
        ->assertJsonStructure(['id', 'url', 'original_name', 'mime_type', 'size']);

    expect($response->json('mime_type'))->toBe('image/jpeg');
    expect($response->json('original_name'))->toBe('photo.jpg');
});

test('authenticated user can upload a png image', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('photo.png'),
        ]);

    $response->assertCreated();
    expect($response->json('mime_type'))->toBe('image/png');
});

test('authenticated user can upload a webp image', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('photo.webp'),
        ]);

    $response->assertCreated();
});

test('authenticated user can upload a pdf document', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->create('document.pdf', 500, 'application/pdf'),
        ]);

    $response->assertCreated();
    expect($response->json('original_name'))->toBe('document.pdf');
});

test('media record is created in database', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('test.jpg'),
        ]);

    $media = Media::query()->first();

    expect($media)->not->toBeNull();
    expect($media->user_id)->toBe($user->id);
    expect($media->disk)->toBe('public');
    expect($media->mediable_type)->toBeNull();
    expect($media->mediable_id)->toBeNull();
});

test('file is stored at correct path pattern', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('test.jpg'),
        ]);

    $media = Media::query()->first();

    $expectedPrefix = sprintf('uploads/%d/%d/%02d/', $user->id, now()->year, now()->month);
    expect($media->path)->toStartWith($expectedPrefix);
    expect($media->path)->toEndWith('.jpg');

    Storage::disk('public')->assertExists($media->path);
});

test('file stored on public disk', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('test.jpg'),
        ]);

    $media = Media::query()->first();
    expect($media->disk)->toBe('public');
});

test('invalid file type is rejected', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

test('file exceeding 50MB is rejected', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('huge.jpg')->size(52000),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

test('missing file is rejected', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('media.upload'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

test('upload returns json with all expected fields', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('photo.jpg', 800, 600)->size(1024),
        ]);

    $response->assertCreated();

    $data = $response->json();

    expect($data)->toHaveKeys(['id', 'url', 'original_name', 'mime_type', 'size']);
    expect($data['id'])->toBeInt();
    expect($data['url'])->toBeString();
    expect($data['original_name'])->toBe('photo.jpg');
    expect($data['mime_type'])->toBe('image/jpeg');
    expect($data['size'])->toBeInt();
});
