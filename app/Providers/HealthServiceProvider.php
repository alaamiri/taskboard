<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\Health\Checks\Checks\DatabaseSizeCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\PingCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class HealthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Health::checks([
            // Infrastructure de base
            DatabaseCheck::new(),
            RedisCheck::new(),
            CacheCheck::new(),

            // Queue et Scheduler
            HorizonCheck::new(),
            QueueCheck::new()
                ->onQueue('default')
                ->failWhenHealthJobTakesLongerThanMinutes(5),
            ScheduleCheck::new()
                ->heartbeatMaxAgeInMinutes(2),

            // Espace disque
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(90),

            // Database avancé
            DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan(50)
                ->failWhenMoreConnectionsThan(100),
            DatabaseSizeCheck::new()
                ->failWhenSizeAboveGb(errorThresholdGb: 5.0),

            // Services externes (si applicable)
            // PingCheck::new()
            //     ->url('https://api.externe.be')
            //     ->name('API Externe'),

            // Production seulement
            ...($this->app->environment('production') ? [
                EnvironmentCheck::new()->expectEnvironment('production'),
                DebugModeCheck::new(),
                // CertificateCheck::new()->url('https://taskboard.be'),
            ] : []),
        ]);
    }
}
