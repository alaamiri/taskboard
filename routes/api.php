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

    Route::get('audit-logs', [App\Http\Controllers\Api\AuditLogController::class, 'index'])
        ->name('api.audit-logs.index');

    // Notifications
    Route::get('notifications', [App\Http\Controllers\Api\NotificationController::class, 'index'])
        ->name('api.notifications.index');
    Route::get('notifications/unread', [App\Http\Controllers\Api\NotificationController::class, 'unread'])
        ->name('api.notifications.unread');
    Route::patch('notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead'])
        ->name('api.notifications.read');
    Route::post('notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead'])
        ->name('api.notifications.read-all');
    Route::delete('notifications/{id}', [App\Http\Controllers\Api\NotificationController::class, 'destroy'])
        ->name('api.notifications.destroy');
});
