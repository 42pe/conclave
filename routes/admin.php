<?php

use App\Http\Controllers\Admin\TopicController;
use Illuminate\Support\Facades\Route;

Route::resource('topics', TopicController::class)->except(['show']);
