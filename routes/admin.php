<?php

use App\Http\Controllers\Admin\TopicController;
use App\Http\Controllers\Admin\UserModerationController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureUserIsAdmin::class])->prefix('admin')->group(function () {
    Route::redirect('/', '/admin/topics');

    Route::get('topics', [TopicController::class, 'index'])->name('admin.topics.index');
    Route::get('topics/create', [TopicController::class, 'create'])->name('admin.topics.create');
    Route::post('topics', [TopicController::class, 'store'])->name('admin.topics.store');
    Route::get('topics/{topic}/edit', [TopicController::class, 'edit'])->name('admin.topics.edit');
    Route::patch('topics/{topic}', [TopicController::class, 'update'])->name('admin.topics.update');
    Route::delete('topics/{topic}', [TopicController::class, 'destroy'])->name('admin.topics.destroy');

    Route::get('users', [UserModerationController::class, 'index'])->name('admin.users.index');
    Route::get('users/create', [UserModerationController::class, 'create'])->name('admin.users.create');
    Route::post('users', [UserModerationController::class, 'store'])->name('admin.users.store');
    Route::post('users/{user}/suspend', [UserModerationController::class, 'suspend'])->name('admin.users.suspend');
    Route::post('users/{user}/unsuspend', [UserModerationController::class, 'unsuspend'])->name('admin.users.unsuspend');
    Route::post('users/{user}/ban', [UserModerationController::class, 'ban'])->name('admin.users.ban');
    Route::delete('users/{user}', [UserModerationController::class, 'destroy'])->name('admin.users.destroy');
});
