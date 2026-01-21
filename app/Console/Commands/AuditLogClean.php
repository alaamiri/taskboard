<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class AuditLogClean extends Command
{
    protected $signature = 'audit:clean {--days=365 : Supprimer les logs plus vieux que X jours}';

    protected $description = 'Supprime les anciennes entrées du journal d\'audit';

    public function handle(): int
    {
        $days = $this->option('days');
        $cutoff = now()->subDays($days);

        $count = Activity::where('created_at', '<', $cutoff)->count();

        if ($count === 0) {
            $this->info('Aucun log à supprimer.');
            return Command::SUCCESS;
        }

        if ($this->confirm("Supprimer {$count} entrées de plus de {$days} jours ?")) {
            Activity::where('created_at', '<', $cutoff)->delete();
            $this->info("✅ {$count} entrées supprimées.");
        }

        return Command::SUCCESS;
    }
}
