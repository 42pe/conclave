<?php

namespace App\Enums;

enum TopicVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Restricted = 'restricted';
}
