<?php

use App\Enums\LocationType;
use App\Models\Location;
use Database\Seeders\LocationSeeder;

test('seeder creates 53 locations', function () {
    $this->seed(LocationSeeder::class);

    expect(Location::count())->toBe(53);
});

test('seeder creates any location with correct type', function () {
    $this->seed(LocationSeeder::class);

    $any = Location::query()->where('iso_code', 'ANY')->first();

    expect($any)->not->toBeNull();
    expect($any->name)->toBe('Any');
    expect($any->type)->toBe(LocationType::Any);
});

test('seeder is idempotent', function () {
    $this->seed(LocationSeeder::class);
    $this->seed(LocationSeeder::class);

    expect(Location::count())->toBe(53);
});
