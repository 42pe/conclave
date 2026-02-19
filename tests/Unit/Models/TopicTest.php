<?php

use App\Enums\TopicVisibility;
use App\Models\Topic;

test('visibility is cast to TopicVisibility enum', function () {
    $topic = new Topic(['visibility' => 'private']);

    expect($topic->visibility)->toBe(TopicVisibility::Private);
});
