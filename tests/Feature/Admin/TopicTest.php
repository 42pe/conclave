<?php

use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// --- Authorization ---

test('guest is redirected to login for topic index', function () {
    $this->get(route('admin.topics.index'))->assertRedirect(route('login'));
});

test('guest is redirected to login for topic create', function () {
    $this->get(route('admin.topics.create'))->assertRedirect(route('login'));
});

test('guest is redirected to login for topic store', function () {
    $this->post(route('admin.topics.store'))->assertRedirect(route('login'));
});

test('guest is redirected to login for topic edit', function () {
    $topic = Topic::factory()->create();

    $this->get(route('admin.topics.edit', $topic))->assertRedirect(route('login'));
});

test('guest is redirected to login for topic update', function () {
    $topic = Topic::factory()->create();

    $this->patch(route('admin.topics.update', $topic))->assertRedirect(route('login'));
});

test('guest is redirected to login for topic destroy', function () {
    $topic = Topic::factory()->create();

    $this->delete(route('admin.topics.destroy', $topic))->assertRedirect(route('login'));
});

test('regular user gets 403 for topic index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.topics.index'))
        ->assertForbidden();
});

test('regular user gets 403 for topic store', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.topics.store'), [
            'title' => 'Test',
            'description' => 'A description',
            'icon' => 'star',
            'visibility' => 'public',
            'sort_order' => 0,
        ])
        ->assertForbidden();
});

test('regular user gets 403 for topic update', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create();

    $this->actingAs($user)
        ->patch(route('admin.topics.update', $topic), [
            'title' => 'Updated',
            'description' => 'A description',
            'icon' => 'star',
            'visibility' => 'public',
            'sort_order' => 0,
        ])
        ->assertForbidden();
});

test('regular user gets 403 for topic destroy', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->create();

    $this->actingAs($user)
        ->delete(route('admin.topics.destroy', $topic))
        ->assertForbidden();
});

test('admin can access topic index', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.topics.index'))
        ->assertSuccessful();
});

test('admin can access topic create page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.topics.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/topics/create')
            ->has('nextSortOrder')
        );
});

test('create page provides next sort order as max plus one', function () {
    $admin = User::factory()->admin()->create();
    Topic::factory()->create(['sort_order' => 5]);
    Topic::factory()->create(['sort_order' => 12]);

    $this->actingAs($admin)
        ->get(route('admin.topics.create'))
        ->assertInertia(fn ($page) => $page
            ->where('nextSortOrder', 13)
        );
});

// --- CRUD ---

test('topic index displays topics', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->create(['title' => 'Test Topic']);

    $this->actingAs($admin)
        ->get(route('admin.topics.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/topics/index')
            ->has('topics', 1)
            ->where('topics.0.title', 'Test Topic')
        );
});

test('admin can store a topic', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => 'New Topic',
            'description' => 'A description',
            'icon' => 'star',
            'visibility' => 'public',
            'sort_order' => 5,
        ]);

    $response->assertRedirect(route('admin.topics.index'));

    $topic = Topic::query()->where('title', 'New Topic')->first();

    expect($topic)->not->toBeNull();
    expect($topic->slug)->toBe('new-topic');
    expect($topic->icon)->toBe('star');
    expect($topic->created_by)->toBe($admin->id);
    expect($topic->sort_order)->toBe(5);
});

test('admin can store a topic with header image', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => 'Image Topic',
            'description' => 'A topic with an image',
            'icon' => 'image',
            'visibility' => 'public',
            'sort_order' => 1,
            'header_image' => UploadedFile::fake()->image('header.jpg', 800, 400),
        ]);

    $topic = Topic::query()->where('title', 'Image Topic')->first();

    expect($topic->header_image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($topic->header_image_path);
});

test('admin can access topic edit page', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.topics.edit', $topic))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/topics/edit')
            ->where('topic.id', $topic->id)
        );
});

test('admin can update a topic', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->create(['title' => 'Old Title']);

    $this->actingAs($admin)
        ->patch(route('admin.topics.update', $topic), [
            'title' => 'New Title',
            'description' => 'Updated description',
            'icon' => 'flame',
            'visibility' => 'private',
            'sort_order' => 10,
        ]);

    $topic->refresh();

    expect($topic->title)->toBe('New Title');
    expect($topic->slug)->toBe('new-title');
    expect($topic->visibility->value)->toBe('private');
    expect($topic->sort_order)->toBe(10);
});

test('admin can update a topic with new header image', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();

    // Create topic with an image
    $oldImage = UploadedFile::fake()->image('old.jpg');
    $oldPath = $oldImage->store('topics/headers', 'public');

    $topic = Topic::factory()->create([
        'header_image_path' => $oldPath,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.topics.update', $topic), [
            'title' => $topic->title,
            'description' => $topic->description ?? 'A description',
            'icon' => $topic->icon ?? 'star',
            'visibility' => $topic->visibility->value,
            'sort_order' => $topic->sort_order,
            'header_image' => UploadedFile::fake()->image('new.jpg', 800, 400),
        ]);

    $topic->refresh();

    expect($topic->header_image_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($topic->header_image_path);
});

test('admin can delete a topic', function () {
    $admin = User::factory()->admin()->create();
    $topic = Topic::factory()->create();
    $topicId = $topic->id;

    $response = $this->actingAs($admin)
        ->delete(route('admin.topics.destroy', $topic));

    $response->assertRedirect(route('admin.topics.index'));

    expect(Topic::find($topicId))->toBeNull();
});

test('deleting a topic removes its header image', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();

    $image = UploadedFile::fake()->image('header.jpg');
    $path = $image->store('topics/headers', 'public');

    $topic = Topic::factory()->create(['header_image_path' => $path]);

    Storage::disk('public')->assertExists($path);

    $this->actingAs($admin)
        ->delete(route('admin.topics.destroy', $topic));

    Storage::disk('public')->assertMissing($path);
});

// --- Validation ---

test('title is required when storing a topic', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'description' => 'A description',
            'icon' => 'star',
            'visibility' => 'public',
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors('title');
});

test('description is required when storing a topic', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => 'Some Topic',
            'icon' => 'star',
            'visibility' => 'public',
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors('description');
});

test('icon is required when storing a topic', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => 'Some Topic',
            'description' => 'A description',
            'visibility' => 'public',
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors('icon');
});

test('visibility is required when storing a topic', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => 'Some Topic',
            'description' => 'A description',
            'icon' => 'star',
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors('visibility');
});

test('sort order is required when storing a topic', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => 'Some Topic',
            'description' => 'A description',
            'icon' => 'star',
            'visibility' => 'public',
        ])
        ->assertSessionHasErrors('sort_order');
});

test('visibility must be a valid enum value', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => 'Some Topic',
            'description' => 'A description',
            'icon' => 'star',
            'visibility' => 'invalid',
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors('visibility');
});

test('header image must be a valid image type', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => 'Some Topic',
            'description' => 'A description',
            'icon' => 'star',
            'visibility' => 'public',
            'sort_order' => 0,
            'header_image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('header_image');
});

test('header image must not exceed 4MB', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.topics.store'), [
            'title' => 'Some Topic',
            'description' => 'A description',
            'icon' => 'star',
            'visibility' => 'public',
            'sort_order' => 0,
            'header_image' => UploadedFile::fake()->image('header.jpg')->size(5000),
        ])
        ->assertSessionHasErrors('header_image');
});
