<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CollaboratorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rotas públicas
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// Rotas protegidas (requerem autenticação)
Route::middleware(['auth:api'])->group(function () { // ✅ Passport: auth:api
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // User management - CRUD básico com policies
    Route::apiResource('users', UserController::class);

    // User management - rotas adicionais
    Route::prefix('users')->group(function () {
        Route::get('search/{search}', [UserController::class, 'search']);
        Route::get('statistics/overview', [UserController::class, 'statistics']);
    });

    // Collaborator management - CRUD básico com policies
    Route::apiResource('collaborators', CollaboratorController::class);

    // Collaborator management - rotas adicionais
    Route::prefix('collaborators')->group(function () {
        Route::get('statistics/overview', [CollaboratorController::class, 'statistics']);
    });
});
