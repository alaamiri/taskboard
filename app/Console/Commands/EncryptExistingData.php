<?php

namespace App\Console\Commands;

use App\Models\Board;
use App\Models\Card;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EncryptExistingData extends Command
{
    protected $signature = 'data:encrypt {--model= : Specific model to encrypt (User, Board, Card)}';

    protected $description = 'Encrypt existing data in the database';

    public function handle(): int
    {
        $model = $this->option('model');

        if (!$model || $model === 'User') {
            $this->encryptUsers();
        }

        if (!$model || $model === 'Board') {
            $this->encryptBoards();
        }

        if (!$model || $model === 'Card') {
            $this->encryptCards();
        }

        $this->info('Encryption completed!');

        return self::SUCCESS;
    }

    private function encryptUsers(): void
    {
        $this->info('Encrypting users...');

        // Lire directement depuis la DB (sans déchiffrement)
        $users = DB::table('users')->get();
        $bar = $this->output->createProgressBar($users->count());

        foreach ($users as $row) {
            // Skip si déjà chiffré
            if (str_starts_with($row->name ?? '', 'nacl:') || str_starts_with($row->name ?? '', 'brng:')) {
                $bar->advance();
                continue;
            }

            // Créer une nouvelle instance et sauvegarder (déclenche le chiffrement)
            $user = User::withoutEvents(function () use ($row) {
                $user = new User();
                $user->exists = true;
                $user->id = $row->id;
                $user->timestamps = false;

                // Assigner les valeurs non chiffrées
                $user->forceFill([
                    'name' => $row->name,
                    'email' => $row->email,
                ]);

                $user->saveQuietly();

                return $user;
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function encryptBoards(): void
    {
        $this->info('Encrypting boards...');

        $boards = DB::table('boards')->get();
        $bar = $this->output->createProgressBar($boards->count());

        foreach ($boards as $row) {
            if (str_starts_with($row->name ?? '', 'nacl:') || str_starts_with($row->name ?? '', 'brng:')) {
                $bar->advance();
                continue;
            }

            Board::withoutEvents(function () use ($row) {
                $board = new Board();
                $board->exists = true;
                $board->id = $row->id;
                $board->timestamps = false;

                $board->forceFill([
                    'name' => $row->name,
                    'description' => $row->description,
                ]);

                $board->saveQuietly();
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function encryptCards(): void
    {
        $this->info('Encrypting cards...');

        $cards = DB::table('cards')->get();
        $bar = $this->output->createProgressBar($cards->count());

        foreach ($cards as $row) {
            if (str_starts_with($row->title ?? '', 'nacl:') || str_starts_with($row->title ?? '', 'brng:')) {
                $bar->advance();
                continue;
            }

            Card::withoutEvents(function () use ($row) {
                $card = new Card();
                $card->exists = true;
                $card->id = $row->id;
                $card->timestamps = false;

                $card->forceFill([
                    'title' => $row->title,
                    'description' => $row->description,
                ]);

                $card->saveQuietly();
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
