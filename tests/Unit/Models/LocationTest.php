<?php

use App\Enums\LocationType;
use App\Models\Location;

test('type is cast to LocationType enum', function () {
    $location = new Location(['type' => 'us_state']);

    expect($location->type)->toBe(LocationType::UsState);
});

test('is_active is cast to boolean', function () {
    $location = new Location(['is_active' => 1]);

    expect($location->is_active)->toBeTrue();

    $location = new Location(['is_active' => 0]);

    expect($location->is_active)->toBeFalse();
});
