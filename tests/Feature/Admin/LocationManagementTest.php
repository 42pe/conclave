<?php

use App\Models\Location;
use App\Models\User;

// --- Authorization ---

test('guest is redirected to login for location index', function () {
    $this->get(route('admin.locations.index'))->assertRedirect(route('login'));
});

test('regular user gets 403 for location index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.locations.index'))
        ->assertForbidden();
});

test('regular user gets 403 for location store', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.locations.store'), [
            'name' => 'Test',
            'iso_code' => 'US-TE',
            'type' => 'us_state',
            'sort_order' => 0,
        ])
        ->assertForbidden();
});

// --- CRUD ---

test('admin can view location list', function () {
    $admin = User::factory()->admin()->create();
    Location::factory()->create(['name' => 'California']);

    $this->actingAs($admin)
        ->get(route('admin.locations.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/locations/index')
            ->has('locations', 1)
            ->where('locations.0.name', 'California')
        );
});

test('admin can view create form', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.locations.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/locations/create')
            ->has('nextSortOrder')
        );
});

test('admin can store a location', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.locations.store'), [
            'name' => 'California',
            'iso_code' => 'US-CA',
            'type' => 'us_state',
            'is_active' => true,
            'sort_order' => 5,
        ]);

    $response->assertRedirect(route('admin.locations.index'));

    $location = Location::query()->where('name', 'California')->first();
    expect($location)->not->toBeNull();
    expect($location->iso_code)->toBe('US-CA');
    expect($location->type->value)->toBe('us_state');
    expect($location->is_active)->toBeTrue();
    expect($location->sort_order)->toBe(5);
});

test('admin can view edit form', function () {
    $admin = User::factory()->admin()->create();
    $location = Location::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.locations.edit', $location))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/locations/edit')
            ->where('location.id', $location->id)
        );
});

test('admin can update a location', function () {
    $admin = User::factory()->admin()->create();
    $location = Location::factory()->create(['name' => 'Old Name']);

    $this->actingAs($admin)
        ->patch(route('admin.locations.update', $location), [
            'name' => 'New Name',
            'iso_code' => $location->iso_code,
            'type' => 'country',
            'is_active' => false,
            'sort_order' => 10,
        ]);

    $location->refresh();
    expect($location->name)->toBe('New Name');
    expect($location->type->value)->toBe('country');
    expect($location->is_active)->toBeFalse();
    expect($location->sort_order)->toBe(10);
});

test('admin can delete a location', function () {
    $admin = User::factory()->admin()->create();
    $location = Location::factory()->create();
    $locationId = $location->id;

    $response = $this->actingAs($admin)
        ->delete(route('admin.locations.destroy', $location));

    $response->assertRedirect(route('admin.locations.index'));
    expect(Location::find($locationId))->toBeNull();
});

// --- Validation ---

test('name is required when storing a location', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.locations.store'), [
            'iso_code' => 'US-CA',
            'type' => 'us_state',
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors('name');
});

test('iso code is required when storing a location', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.locations.store'), [
            'name' => 'California',
            'type' => 'us_state',
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors('iso_code');
});

test('duplicate iso code is rejected', function () {
    $admin = User::factory()->admin()->create();
    Location::factory()->create(['iso_code' => 'US-CA']);

    $this->actingAs($admin)
        ->post(route('admin.locations.store'), [
            'name' => 'California Duplicate',
            'iso_code' => 'US-CA',
            'type' => 'us_state',
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors('iso_code');
});

test('type must be a valid enum value', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.locations.store'), [
            'name' => 'Test',
            'iso_code' => 'XX-XX',
            'type' => 'invalid',
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors('type');
});

test('update allows same iso code for current location', function () {
    $admin = User::factory()->admin()->create();
    $location = Location::factory()->create(['iso_code' => 'US-CA']);

    $this->actingAs($admin)
        ->patch(route('admin.locations.update', $location), [
            'name' => 'Updated California',
            'iso_code' => 'US-CA',
            'type' => 'us_state',
            'sort_order' => 0,
        ])
        ->assertSessionDoesntHaveErrors('iso_code');

    $location->refresh();
    expect($location->name)->toBe('Updated California');
});
