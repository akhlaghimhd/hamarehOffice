<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\PartyController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies/switch', [CompanyController::class, 'switch']);

    Route::middleware('company.context')->group(function () {
        Route::get('/parties', [PartyController::class, 'index']);
        Route::post('/parties', [PartyController::class, 'store']);
    });
});
