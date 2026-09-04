<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\TransactionController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/themes', [ThemeController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'show']);
    Route::put('/user/theme', [UserController::class, 'updateTheme']);

    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/groups', [GroupController::class, 'store']);
    Route::post('/groups/{id}/invite', [GroupController::class, 'invite']);
    Route::delete('/groups/{id}/remove-member/{user_id}', [GroupController::class, 'removeMember']);

    Route::get('/groups/{group_id}/transactions', [TransactionController::class, 'index']);
    Route::post('/groups/{group_id}/transactions', [TransactionController::class, 'store']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
    Route::get('/groups/{group_id}/transactions/summary', [TransactionController::class, 'summary']);
    Route::get('/groups/{group_id}/analytics', [TransactionController::class, 'analytics']);
});
