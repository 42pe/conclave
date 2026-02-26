<?php

use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/directory', [DirectoryController::class, 'index'])
    ->name('directory.index');

Route::get('/users/search', UserSearchController::class)
    ->middleware(['auth', 'verified'])
    ->name('users.search');

Route::get('/users/{user:username}', [UserProfileController::class, 'show'])
    ->name('users.show');

Route::get('/topics/{topic:slug}', [DiscussionController::class, 'index'])
    ->name('topics.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/topics/{topic:slug}/discussions/create', [DiscussionController::class, 'create'])
        ->middleware('not-suspended')
        ->name('topics.discussions.create');

    Route::post('/topics/{topic:slug}/discussions', [DiscussionController::class, 'store'])
        ->middleware('not-suspended')
        ->name('topics.discussions.store');

    Route::get('/topics/{topic:slug}/discussions/{discussion:slug}/edit', [DiscussionController::class, 'edit'])
        ->scopeBindings()
        ->name('topics.discussions.edit');

    Route::patch('/topics/{topic:slug}/discussions/{discussion:slug}', [DiscussionController::class, 'update'])
        ->scopeBindings()
        ->name('topics.discussions.update');

    Route::delete('/topics/{topic:slug}/discussions/{discussion:slug}', [DiscussionController::class, 'destroy'])
        ->scopeBindings()
        ->name('topics.discussions.destroy');

    Route::post('/discussions/{discussion}/replies', [ReplyController::class, 'store'])
        ->middleware('not-suspended')
        ->name('discussions.replies.store');

    Route::patch('/replies/{reply}', [ReplyController::class, 'update'])
        ->name('replies.update');

    Route::delete('/replies/{reply}', [ReplyController::class, 'destroy'])
        ->name('replies.destroy');

    Route::post('/discussions/{discussion}/like', [LikeController::class, 'toggleDiscussionLike'])
        ->middleware('not-suspended')
        ->name('discussions.like');

    Route::post('/replies/{reply}/like', [LikeController::class, 'toggleReplyLike'])
        ->middleware('not-suspended')
        ->name('replies.like');
});

Route::get('/topics/{topic:slug}/discussions/{discussion:slug}', [DiscussionController::class, 'show'])
    ->scopeBindings()
    ->name('topics.discussions.show');
