<?php

use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\ColumnController;
use App\Http\Controllers\Api\CardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Ajoute ->names('api.boards') pour préfixer les noms
    Route::apiResource('boards', BoardController::class)->names('api.boards');

    Route::post('boards/{board}/columns', [ColumnController::class, 'store'])
        ->name('api.columns.store');
    Route::put('columns/{column}', [ColumnController::class, 'update'])
        ->name('api.columns.update');
    Route::delete('columns/{column}', [ColumnController::class, 'destroy'])
        ->name('api.columns.destroy');

    Route::post('columns/{column}/cards', [CardController::class, 'store'])
        ->name('api.cards.store');
    Route::put('cards/{card}', [CardController::class, 'update'])
        ->name('api.cards.update');
    Route::delete('cards/{card}', [CardController::class, 'destroy'])
        ->name('api.cards.destroy');
    Route::patch('cards/{card}/move', [CardController::class, 'move'])
        ->name('api.cards.move');
});
