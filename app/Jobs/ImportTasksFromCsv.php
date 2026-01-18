<?php

namespace App\Jobs;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportTasksFromCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives en cas d'échec
     */
    public int $tries = 3;

    /**
     * Timeout en secondes
     */
    public int $timeout = 300;

    public function __construct(
        public Board $board,
        public string $filePath,
        public int $userId
    ) {}

    public function handle(): void
    {
        Log::info("Import démarré pour board {$this->board->id}");

        // Trouve ou crée une colonne "Imported"
        $column = $this->board->columns()->firstOrCreate(
            ['name' => 'Imported'],
            ['position' => $this->board->columns()->count()]
        );

        // Lit le fichier CSV
        $file = Storage::get($this->filePath);
        $lines = explode("\n", $file);

        // Retire l'en-tête
        $header = str_getcsv(array_shift($lines));

        $imported = 0;
        $position = $column->cards()->count();

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $data = str_getcsv($line);

            // Suppose que le CSV a les colonnes: title, description
            $title = $data[0] ?? null;
            $description = $data[1] ?? null;

            if (!$title) continue;

            Card::create([
                'title' => $title,
                'description' => $description,
                'column_id' => $column->id,
                'user_id' => $this->userId,
                'position' => $position++
            ]);

            $imported++;
        }

        // Supprime le fichier temporaire
        Storage::delete($this->filePath);

        Log::info("Import terminé: {$imported} cartes importées");
        // Broadcast pour rafraîchir les clients connectés
        broadcast(new \App\Events\BoardUpdated($this->board));
    }

    /**
     * En cas d'échec
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Import échoué: " . $exception->getMessage());
        Storage::delete($this->filePath);
    }
}
