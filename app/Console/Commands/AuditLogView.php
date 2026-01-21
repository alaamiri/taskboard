<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class AuditLogView extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:view
                            {--limit=20 : Nombre d\'entrées à afficher}
                            {--user= : Filtrer par user ID}
                            {--model= : Filtrer par type de model (Board, Column, Card)}
                            {--exceptions : Afficher uniquement les exceptions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Affiche les entrées du journal d\'audit';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = Activity::with('causer')
            ->latest()
            ->limit($this->option('limit'));

        if ($userId = $this->option('user')) {
            $query->where('causer_id', $userId);
        }

        if ($model = $this->option('model')) {
            $query->where('subject_type', 'like', "%{$model}%");
        }

        if ($this->option('exceptions')) {
            $query->where('description', 'exception');
        }

        $activities = $query->get();

        if ($activities->isEmpty()) {
            $this->info('Aucune activité trouvée');
            return Command::SUCCESS;
        }
        $rows = $activities->map(function ($activity) {
            return [
                $activity->id,
                $activity->causer?->name ?? 'Système',
                $activity->description,
                class_basename($activity->subject_type ?? 'N/A'),
                $activity->subject_id ?? 'N/A',
                $activity->created_at->format('Y-m-d H:i:s'),
            ];
        });

        $this->table(
            ['ID', 'Utilisateur', 'Action', 'Model', 'Model ID', 'Date'],
            $rows
        );

        return Command::SUCCESS;
    }
}
