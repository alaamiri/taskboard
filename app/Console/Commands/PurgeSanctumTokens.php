<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class PurgeSanctumTokens extends Command
{
    protected $signature = 'tokens:purge {--days=30 : Nombre de jours d\'inactivité}';

    protected $description = 'Supprime les tokens Sanctum expirés ou non utilisés';

    public function handle(): int
    {
        $days = $this->option('days');
        $cutoff = now()->subDays($days);

        // Supprime les tokens non utilisés depuis X jours
        $deletedUnused = PersonalAccessToken::where('last_used_at', '<', $cutoff)
            ->orWhereNull('last_used_at')
            ->where('created_at', '<', $cutoff)
            ->delete();

        // Supprime les tokens avec expiration dépassée (si tu utilises expires_at)
        $deletedExpired = PersonalAccessToken::where('expires_at', '<', now())->delete();

        $total = $deletedUnused + $deletedExpired;

        $this->info("✅ {$total} tokens supprimés.");

        return Command::SUCCESS;
    }
}
