<?php


use Illuminate\Support\Facades\Route;
use Mzm\Sso\Http\Controllers\SsoController;


Route::middleware('web')->group(function () {
    Route::get('sso/auth', SsoController::class)->name('sso.auth');
});
