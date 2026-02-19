<?php

use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\ReplyController;
use Illuminate\Support\Facades\Route;

// Topic discussions listing (public or auth depending on visibility)
Route::get('topics/{topic:slug}', [DiscussionController::class, 'index'])->name('topics.show');

// Discussion routes
Route::get('topics/{topic:slug}/discussions/create', [DiscussionController::class, 'create'])
    ->middleware(['auth', 'verified'])
    ->name('discussions.create');

Route::post('discussions', [DiscussionController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('discussions.store');

Route::get('topics/{topic:slug}/discussions/{discussion:slug}', [DiscussionController::class, 'show'])
    ->name('discussions.show');

Route::get('topics/{topic:slug}/discussions/{discussion:slug}/edit', [DiscussionController::class, 'edit'])
    ->middleware(['auth', 'verified'])
    ->name('discussions.edit');

Route::patch('topics/{topic:slug}/discussions/{discussion:slug}', [DiscussionController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('discussions.update');

Route::delete('topics/{topic:slug}/discussions/{discussion:slug}', [DiscussionController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('discussions.destroy');

// Reply routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('replies', [ReplyController::class, 'store'])->name('replies.store');
    Route::patch('replies/{reply}', [ReplyController::class, 'update'])->name('replies.update');
    Route::delete('replies/{reply}', [ReplyController::class, 'destroy'])->name('replies.destroy');
});
