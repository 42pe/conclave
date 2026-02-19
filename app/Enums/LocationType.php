<?php

namespace App\Enums;

enum LocationType: string
{
    case Any = 'any';
    case UsState = 'us_state';
    case Country = 'country';
}
