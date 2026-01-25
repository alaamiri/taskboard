<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;
use Spatie\Health\Commands\DispatchQueueCheckJobsCommand;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Purge les tokens Sanctum tous les jours à 2h du matin
Schedule::command('tokens:purge --days=30')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->emailOutputOnFailure(env('ADMIN_EMAIL'));

// Nettoie les fichiers d'import toutes les 6 heures
Schedule::command('imports:clean --hours=24')
    ->everySixHours()
    ->withoutOverlapping()
    ->onOneServer();

// Nettoie les logs d'audit de plus d'un an, chaque mois
Schedule::command('audit:clean --days=365')
    ->monthly()
    ->withoutOverlapping()
    ->onOneServer();

//Laravel Health heartbeat
Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute();
Schedule::command(DispatchQueueCheckJobsCommand::class)->everyMinute();
