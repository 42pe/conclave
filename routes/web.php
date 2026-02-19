<?php

use App\Enums\TopicVisibility;
use App\Http\Controllers\MediaController;
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
        ->when(! $user, fn ($q) => $q->where('visibility', TopicVisibility::Public))
        ->when($user && ! $user->isAdminOrModerator(), fn ($q) => $q->whereIn('visibility', [
            TopicVisibility::Public,
            TopicVisibility::Private,
        ]))
        ->get();

    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'topics' => $topics,
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('media/upload', [MediaController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('media.upload');

require __DIR__.'/settings.php';
