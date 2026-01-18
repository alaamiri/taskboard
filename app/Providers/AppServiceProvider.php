<?php

namespace App\Providers;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Policies\BoardPolicy;
use App\Policies\CardPolicy;
use App\Policies\ColumnPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Gate::policy(Board::class, BoardPolicy::class);
        Gate::policy(Column::class, ColumnPolicy::class);
        Gate::policy(Card::class, CardPolicy::class);
    }
}
