<?php

use App\Enums\LocationType;
use App\Models\Location;

test('location active scope filters correctly', function () {
    Location::factory()->create(['is_active' => true]);
    Location::factory()->create(['is_active' => true]);
    Location::factory()->create(['is_active' => false]);

    $active = Location::active()->get();

    expect($active)->toHaveCount(2);
    $active->each(fn ($location) => expect($location->is_active)->toBeTrue());
});

test('location byType scope filters correctly', function () {
    Location::factory()->create(['type' => LocationType::UsState]);
    Location::factory()->create(['type' => LocationType::UsState]);
    Location::factory()->create(['type' => LocationType::Country]);
    Location::factory()->create(['type' => LocationType::Any]);

    $states = Location::byType(LocationType::UsState)->get();
    $countries = Location::byType(LocationType::Country)->get();
    $any = Location::byType(LocationType::Any)->get();

    expect($states)->toHaveCount(2);
    expect($countries)->toHaveCount(1);
    expect($any)->toHaveCount(1);
});

test('location factory creates valid location', function () {
    $location = Location::factory()->create();

    expect($location->name)->toBeString();
    expect($location->iso_code)->toBeString();
    expect($location->type)->toBeInstanceOf(LocationType::class);
    expect($location->is_active)->toBeBool();
});

test('location seeder creates expected records', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\Development\\LocationSeeder']);

    $total = Location::count();
    expect($total)->toBe(53);

    $any = Location::byType(LocationType::Any)->count();
    expect($any)->toBe(1);

    $states = Location::byType(LocationType::UsState)->count();
    expect($states)->toBe(50);

    $countries = Location::byType(LocationType::Country)->count();
    expect($countries)->toBe(2);
});

test('location iso_code is unique', function () {
    Location::factory()->create(['iso_code' => 'US-CA']);

    expect(fn () => Location::factory()->create(['iso_code' => 'US-CA']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
