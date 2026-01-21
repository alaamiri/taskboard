<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ColumnController;
use App\Http\Controllers\CardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('boards.index');
    }
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Toutes les routes CRUD pour Board
    Route::resource('boards', BoardController::class);

    // Columns - imbriquées dans boards
    Route::post('/boards/{board}/columns', [ColumnController::class, 'store'])
        ->name('columns.store');
    Route::put('/columns/{column}', [ColumnController::class, 'update'])
        ->name('columns.update');
    Route::delete('/columns/{column}', [ColumnController::class, 'destroy'])
        ->name('columns.destroy');

    // Cards - imbriquées dans columns
    Route::post('/columns/{column}/cards', [CardController::class, 'store'])
        ->name('cards.store');
    Route::put('/cards/{card}', [CardController::class, 'update'])
        ->name('cards.update');
    Route::delete('/cards/{card}', [CardController::class, 'destroy'])
        ->name('cards.destroy');
    Route::patch('/cards/{card}/move', [CardController::class, 'move'])
        ->name('cards.move');

    // ImportController - Horizon
    Route::post('/boards/{board}/import', [ImportController::class, 'store'])
        ->name('boards.import');

    // Health checks
    Route::get('/health', HealthCheckJsonResultsController::class)->name('health');
    Route::get('/health/dashboard', HealthCheckResultsController::class)->name('health.dashboard');
});

require __DIR__.'/auth.php';
