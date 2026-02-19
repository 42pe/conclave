<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// --- Successful uploads ---

test('authenticated user can upload an image', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('photo.jpg', 800, 600)->size(500),
        ]);

    $response->assertCreated();
    $response->assertJsonStructure([
        'id',
        'url',
        'original_name',
        'mime_type',
    ]);
    expect($response->json('original_name'))->toBe('photo.jpg');
    expect($response->json('mime_type'))->toContain('image/');
});

test('authenticated user can upload a png image', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('screenshot.png', 1024, 768)->size(800),
        ]);

    $response->assertCreated();
    expect($response->json('original_name'))->toBe('screenshot.png');
});

test('authenticated user can upload a webp image', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('photo.webp', 400, 300)->size(200),
        ]);

    $response->assertCreated();
    expect($response->json('original_name'))->toBe('photo.webp');
});

test('authenticated user can upload a gif image', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('animation.gif', 200, 200)->size(300),
        ]);

    $response->assertCreated();
    expect($response->json('original_name'))->toBe('animation.gif');
});

test('authenticated user can upload a video', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->create('video.mp4', 5000, 'video/mp4'),
        ]);

    $response->assertCreated();
    expect($response->json('original_name'))->toBe('video.mp4');
    expect($response->json('mime_type'))->toBe('video/mp4');
});

test('authenticated user can upload a webm video', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->create('clip.webm', 3000, 'video/webm'),
        ]);

    $response->assertCreated();
    expect($response->json('original_name'))->toBe('clip.webm');
});

test('authenticated user can upload a pdf document', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
        ]);

    $response->assertCreated();
    expect($response->json('original_name'))->toBe('document.pdf');
    expect($response->json('mime_type'))->toBe('application/pdf');
});

// --- File storage path ---

test('uploaded file is stored at correct path pattern', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('photo.jpg', 800, 600)->size(500),
        ]);

    $response->assertCreated();

    $media = Media::first();
    expect($media)->not->toBeNull();

    // Path should match pattern: uploads/{user_id}/{Y}/{m}/{uuid}.{ext}
    $expectedPrefix = "uploads/{$user->id}/" . now()->format('Y') . '/' . now()->format('m') . '/';
    expect($media->path)->toStartWith($expectedPrefix);
    expect($media->path)->toEndWith('.jpg');

    Storage::disk('public')->assertExists($media->path);
});

// --- Media DB record ---

test('media database record is created correctly on upload', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('photo.jpg', 800, 600)->size(500),
        ]);

    $media = Media::first();
    expect($media)->not->toBeNull();
    expect($media->user_id)->toBe($user->id);
    expect($media->original_name)->toBe('photo.jpg');
    expect($media->mime_type)->toContain('image/');
    expect($media->disk)->toBe('public');
    expect($media->size)->toBeGreaterThan(0);
});

test('upload response includes media id and url', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('photo.jpg', 800, 600)->size(500),
        ]);

    $response->assertCreated();
    $data = $response->json();

    expect($data)->toHaveKey('id');
    expect($data)->toHaveKey('url');
    expect($data['id'])->toBeInt();
    expect($data['url'])->toBeString();
});

// --- Rejection: unauthenticated ---

test('unauthenticated user cannot upload media', function () {
    Storage::fake('public');

    $response = $this->postJson(route('media.upload'), [
        'file' => UploadedFile::fake()->image('photo.jpg', 800, 600),
    ]);

    $response->assertUnauthorized();
});

// --- Rejection: unsupported file types ---

test('upload rejects unsupported file type exe', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload'),
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('file');
});

test('upload rejects zip files', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->create('archive.zip', 100, 'application/zip'),
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('file');
});

test('upload rejects text files', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->create('readme.txt', 10, 'text/plain'),
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('file');
});

// --- Rejection: oversized files ---

test('upload rejects oversized image', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    // Images should have a max size (e.g., 10MB)
    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->image('huge.jpg', 4000, 4000)->size(11000),
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('file');
});

test('upload rejects oversized video', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    // Videos may have higher limit but still capped (e.g., 50MB)
    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), [
            'file' => UploadedFile::fake()->create('massive.mp4', 60000, 'video/mp4'),
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('file');
});

// --- Rejection: missing file ---

test('upload fails without file', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postJson(route('media.upload'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('file');
});
