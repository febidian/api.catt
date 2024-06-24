<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('register', 'register')->middleware('throttle:register')->name('auth.register');
    Route::post('login', 'login')->middleware('throttle:login')->name('auth.login');
    Route::get('login/google', 'redirectToGoogle')->name('auth.redirectToGoogle');
    Route::get('login/google/callback', 'handleGoogleCallback')->name('auth.handleGoogleCallback');
    Route::get('login/facebook', 'redirectToFacebook')->name('auth.redirectToFacebook');
    Route::get('login/facebook/callback', 'handleFacebookCallback')->name('auth.handleFacebookCallback');
    Route::post('refresh', 'refresh')->name('auth.refresh');
    Route::post('logout', 'logout')->middleware('auth:api')->name('auth.logout');
});

Route::controller(NoteController::class)->prefix('note')->middleware('auth:api')->group(function () {
    // Route::get('', 'index')->name('note.index');
    Route::post('', 'create')->middleware("throttle:note")->name('note.create');
    // Route::get('{note}', 'show')->name('note.show');
    // Route::put('{note}', 'update')->name('note.update');
    // Route::delete('{note}', 'destroy')->name('note.destroy');
    Route::post('upload/image', 'uploadImage')->name('note.uploadImage');
    Route::get('category', 'category')->name('note.category');
});
