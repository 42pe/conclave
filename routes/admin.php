<?php

use App\Http\Controllers\Admin\TopicController;
use App\Http\Controllers\Admin\UserModerationController;
use Illuminate\Support\Facades\Route;

Route::resource('topics', TopicController::class)->except(['show']);

Route::get('users', [UserModerationController::class, 'index'])->name('users.index');
Route::get('users/create', [UserModerationController::class, 'create'])->name('users.create');
Route::post('users', [UserModerationController::class, 'store'])->name('users.store');
Route::post('users/{user}/suspend', [UserModerationController::class, 'suspend'])->name('users.suspend');
Route::post('users/{user}/unsuspend', [UserModerationController::class, 'unsuspend'])->name('users.unsuspend');
Route::post('users/{user}/ban', [UserModerationController::class, 'ban'])->name('users.ban');
Route::post('users/{user}/delete', [UserModerationController::class, 'delete'])->name('users.delete');
