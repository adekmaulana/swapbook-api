<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Broadcast;
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
                Route::get('/csrf-cookie', [AuthController::class, 'csrfCookie']);
                Route::get('/ping', [AuthController::class, 'ping'])->middleware('auth:sanctum');
            }
        );

        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        Route::group(
            ['middleware' => 'auth:sanctum'],
            function () {
                Route::get('/user', [UserController::class, 'get']);
                Route::post('/user', [UserController::class, 'update']);
                Route::post('/user/location', [UserController::class, 'updateLocation']);
                Route::get('/users', [UserController::class, 'users']);

                // Chat
                Route::post('/chat', [ChatController::class, 'createChat']);
                Route::get('/chat/{chat}', [ChatController::class, 'getChat']);
                Route::get('/chats', [ChatController::class, 'getChats']);

                // Message
                Route::get('/messages', [MessageController::class, 'getMessages']);
                Route::post('/message', [MessageController::class, 'sendMessage']);
                Route::get('/message/{message}', [MessageController::class, 'getMessage']);
                Route::post('/message/{message}', [MessageController::class, 'editMessage']);
                Route::post('/messages/read', [MessageController::class, 'readMessages']);

                // Post or Book
                Route::post('/post', [BookController::class, 'createBook']);
                Route::get('/post/{post}', [BookController::class, 'getBook']);
                Route::get('/posts', [BookController::class, 'getBooks']);
                Route::post('/post/bookmark', [BookController::class, 'bookmark']);
            }
        );
    }
);
