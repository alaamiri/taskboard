<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanImportFiles extends Command
{
    protected $signature = 'imports:clean {--hours=24 : Nombre d\'heures avant suppression}';

    protected $description = 'Supprime les fichiers d\'import CSV temporaires';

    public function handle(): int
    {
        $hours = $this->option('hours');
        $cutoff = now()->subHours($hours);
        $deleted = 0;

        $files = Storage::files('imports');

        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);
            $fileTime = \Carbon\Carbon::createFromTimestamp($lastModified);

            if ($fileTime->lt($cutoff)) {
                Storage::delete($file);
                $this->line("Supprimé: {$file}");
                $deleted++;
            }
        }

        $this->info("✅ {$deleted} fichiers d'import supprimés.");

        return Command::SUCCESS;
    }
}
