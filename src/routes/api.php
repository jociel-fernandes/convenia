<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CollaboratorController;
use App\Http\Controllers\Api\CollaboratorImportController;
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

    // Collaborator Import/Export - rotas de importação e exportação (ANTES do apiResource)
    Route::prefix('collaborators/import')->group(function () {
        Route::get('/', [CollaboratorImportController::class, 'index']);
        Route::post('/', [CollaboratorImportController::class, 'upload']);
        Route::post('validate', [CollaboratorImportController::class, 'validate']);
        Route::get('template', [CollaboratorImportController::class, 'template']);
        Route::get('{import}/status', [CollaboratorImportController::class, 'status']);
        Route::post('{import}/cancel', [CollaboratorImportController::class, 'cancel']);
    });

    Route::prefix('collaborators/export')->group(function () {
        Route::post('/', [CollaboratorImportController::class, 'export']);
    });

    // Collaborator management - CRUD básico com policies
    Route::apiResource('collaborators', CollaboratorController::class);

    // Collaborator management - rotas adicionais
    Route::prefix('collaborators')->group(function () {
        Route::get('statistics/overview', [CollaboratorController::class, 'statistics']);
    });
});
