<?php

use App\Http\Controllers\Admin\TopicController;
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
});
