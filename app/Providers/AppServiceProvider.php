<?php

namespace App\Providers;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Policies\BoardPolicy;
use App\Policies\CardPolicy;
use App\Policies\ColumnPolicy;
use App\Repositories\Contracts\BoardRepositoryInterface;
use App\Repositories\Contracts\CardRepositoryInterface;
use App\Repositories\Contracts\ColumnRepositoryInterface;
use App\Repositories\Eloquent\BoardRepository;
use App\Repositories\Eloquent\CardRepository;
use App\Repositories\Eloquent\ColumnRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->bind(BoardRepositoryInterface::class, BoardRepository::class);
        $this->app->bind(ColumnRepositoryInterface::class, ColumnRepository::class);
        $this->app->bind(CardRepositoryInterface::class, CardRepository::class);
    }

    public function boot(): void
    {
        // Policies
        Gate::policy(Board::class, BoardPolicy::class);
        Gate::policy(Column::class, ColumnPolicy::class);
        Gate::policy(Card::class, CardPolicy::class);
    }
}
