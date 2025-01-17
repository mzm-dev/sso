<?php


use Illuminate\Support\Facades\Route;
use Mzm\Sso\Http\Controllers\SsoController;


Route::middleware('web')->group(function () {
    Route::get('sso/auth', function () {
        return view('sso::auth');
    })->name('sso.auth');

    Route::get('sso/verify', SsoController::class)->name('sso.verify');
});
