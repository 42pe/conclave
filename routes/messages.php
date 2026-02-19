<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('messages', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('conversations/start/{user}', [ConversationController::class, 'start'])->name('conversations.start');
    Route::get('conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');

    Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
});
