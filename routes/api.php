<?php

use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\ColumnController;
use App\Http\Controllers\Api\CardController;
use Illuminate\Support\Facades\Route;

// Toutes les routes API nécessitent une authentification
Route::middleware('auth:sanctum')->group(function () {
    
    // Boards
    Route::apiResource('boards', BoardController::class);
    
    // Columns
    Route::post('boards/{board}/columns', [ColumnController::class, 'store']);
    Route::put('columns/{column}', [ColumnController::class, 'update']);
    Route::delete('columns/{column}', [ColumnController::class, 'destroy']);
    
    // Cards
    Route::post('columns/{column}/cards', [CardController::class, 'store']);
    Route::put('cards/{card}', [CardController::class, 'update']);
    Route::delete('cards/{card}', [CardController::class, 'destroy']);
    Route::patch('cards/{card}/move', [CardController::class, 'move']);
});
