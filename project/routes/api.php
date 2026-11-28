<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\PartyController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies/switch', [CompanyController::class, 'switch']);
    Route::post('/companies', [CompanyController::class, 'store']);

    Route::middleware('company.context')->group(function () {
        Route::get('/parties', [PartyController::class, 'index']);
        Route::post('/parties', [PartyController::class, 'store']);

        Route::get('/company-users', [CompanyController::class, 'users']);

        Route::get('/opportunities', [OpportunityController::class, 'index']);
        Route::post('/opportunities', [OpportunityController::class, 'store']);
        Route::get('/opportunities/{id}', [OpportunityController::class, 'show']);
        Route::patch('/opportunities/{id}', [OpportunityController::class, 'update']);
        Route::post('/opportunities/{id}/close-step', [OpportunityController::class, 'closeStep']);
        Route::post('/opportunities/{id}/open-step', [OpportunityController::class, 'openStep']);
        Route::post('/opportunities/{id}/activities', [OpportunityController::class, 'storeActivity']);
        Route::post('/opportunities/{id}/assign', [OpportunityController::class, 'assign']);
        Route::get('/opportunities/{id}/insight', [OpportunityController::class, 'insight']);
        Route::get('/opportunity-stages', [OpportunityController::class, 'stages']);
    });
});
