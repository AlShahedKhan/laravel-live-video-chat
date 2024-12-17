<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoChatController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/video-chat', [VideoChatController::class, 'index'])->name('video.chat');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
