<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(
    function () {
        Route::group(
            ['prefix' => 'auth'],
            function () {
                Route::post('/register', [AuthController::class, 'register']);
                Route::post('/login', [AuthController::class, 'login'])->name('login');
                Route::post('/login-google', [AuthController::class, 'loginGoogle'])->name('login.google');
                Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
                Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
                Route::post('/reset-password', [AuthController::class, 'resetPasswordProcess'])->name('password.update');
                Route::get('/reset-password/{token}', [AuthController::class, 'resetPassword'])->name('password.reset');
            }
        );

        Route::get('/user', function (Request $request) {
            return $request->user();
        })->middleware('auth:sanctum');
    }
);
