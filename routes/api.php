<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VacationController;
use App\Http\Controllers\API\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/set-password', [AuthController::class, 'setPassword']);
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/logout', [AuthController::class, 'logout']);
Route::post('refresh', [AuthController::class, 'refresh']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('users/me', [AuthController::class, 'me']);
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{userId}', [UserController::class, 'show']);
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{userId}', [UserController::class, 'update']);
    Route::delete('users/{userId}', [UserController::class, 'delete']);

    Route::get('vacations', [VacationController::class, 'index']);
    Route::get('vacations/my-vacations', [VacationController::class, 'myVacations']);
    Route::post('vacations', [VacationController::class, 'store']);
    Route::put('vacations/{vacationId}', [VacationController::class, 'update']);
    Route::put('vacations/{vacationId}/status', [VacationController::class, 'updateStatus']);
    Route::delete('vacations/{vacationId}', [VacationController::class, 'destroy']);

    Route::get('customers', [ClientController::class, 'index']);
    Route::post('customers', [ClientController::class, 'store']);
    Route::get('customers/{customerId}', [ClientController::class, 'show']);
    Route::put('customers/{customerId}', [ClientController::class, 'update']);
    Route::delete('customers/{customerId}', [ClientController::class, 'destroy']);
});