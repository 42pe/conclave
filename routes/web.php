<?php

use App\Enums\TopicVisibility;
use App\Models\Topic;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    $user = request()->user();
    $topics = Topic::query()
        ->withCount('discussions')
        ->orderBy('sort_order')
        ->orderBy('title')
        ->get()
        ->filter(function (Topic $topic) use ($user) {
            return match ($topic->visibility) {
                TopicVisibility::Public => true,
                TopicVisibility::Private => $user !== null,
                TopicVisibility::Restricted => $user?->isAdminOrModerator() ?? false,
            };
        })
        ->values();

    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'topics' => $topics,
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/media.php';
require __DIR__.'/forum.php';
require __DIR__.'/profile.php';
require __DIR__.'/messages.php';
