<?php

use App\Http\Controllers\DiscussionController;
use Illuminate\Support\Facades\Route;

Route::get('/topics/{topic:slug}', [DiscussionController::class, 'index'])
    ->name('topics.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/topics/{topic:slug}/discussions/create', [DiscussionController::class, 'create'])
        ->name('topics.discussions.create');

    Route::post('/topics/{topic:slug}/discussions', [DiscussionController::class, 'store'])
        ->name('topics.discussions.store');

    Route::get('/topics/{topic:slug}/discussions/{discussion:slug}/edit', [DiscussionController::class, 'edit'])
        ->name('topics.discussions.edit');

    Route::patch('/topics/{topic:slug}/discussions/{discussion:slug}', [DiscussionController::class, 'update'])
        ->name('topics.discussions.update');

    Route::delete('/topics/{topic:slug}/discussions/{discussion:slug}', [DiscussionController::class, 'destroy'])
        ->name('topics.discussions.destroy');
});

Route::get('/topics/{topic:slug}/discussions/{discussion:slug}', [DiscussionController::class, 'show'])
    ->name('topics.discussions.show');
