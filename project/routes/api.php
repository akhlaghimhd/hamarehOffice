<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies/switch', [CompanyController::class, 'switch']);

    // Example of company-scoped route group for later modules:
    // Route::middleware('company.context')->group(function () { ... });
});
