<?php

use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::get('directory', [DirectoryController::class, 'index'])->name('directory');
Route::get('users/{username}', [UserProfileController::class, 'show'])->name('users.show');
