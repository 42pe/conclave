<?php

use App\Enums\TopicVisibility;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MessageController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('messages', [ConversationController::class, 'index'])
        ->name('conversations.index');
    Route::post('messages', [ConversationController::class, 'store'])
        ->middleware('not-suspended')
        ->name('conversations.store');
    Route::get('messages/{conversation}', [ConversationController::class, 'show'])
        ->name('conversations.show');
    Route::post('messages/{conversation}/reply', [MessageController::class, 'store'])
        ->name('messages.store');
});

require __DIR__.'/settings.php';
