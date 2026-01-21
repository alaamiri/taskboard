<?php

namespace App\Services;

use App\Data\Board\BoardData;
use App\Exceptions\Board\BoardDeletionFailedException;
use App\Models\Board;
use App\Models\User;
use App\Repositories\Contracts\BoardRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class BoardService
{
    public function __construct(
        private readonly BoardRepositoryInterface $boardRepository
    ) {}

    public function getAllForUser(User $user): Collection
    {
        if ($user->hasRole('admin')) {
            return $this->boardRepository->getAll();
        }

        return $this->boardRepository->getAllForUser($user);
    }

    public function getWithRelations(Board $board): Board
    {
        return $this->boardRepository->findByIdWithRelations($board->id);
    }

    /**
     * @throws \Illuminate\Database\QueryException
     * @throws \Throwable
     */
    public function create(User $user, BoardData $data): Board
    {
        return DB::transaction(function () use ($user, $data) {
            $board = $this->boardRepository->create([
                'name' => $data->name,
                'description' => $data->description,
                'user_id' => $user->id,
            ]);

            // Créer les colonnes par défaut
            $board->columns()->createMany([
                ['name' => 'À faire', 'position' => 0],
                ['name' => 'En cours', 'position' => 1],
                ['name' => 'Terminé', 'position' => 2],
            ]);

            return $board;
        });
    }

    public function update(Board $board, BoardData $data): Board
    {
        $updateData = [];

        if (!$data->name instanceof Optional) {
            $updateData['name'] = $data->name;
        }

        if ($data->description !== null || !$data->name instanceof Optional) {
            $updateData['description'] = $data->description;
        }

        return $this->boardRepository->update($board, $updateData);
    }

    /**
     * @throws \Illuminate\Database\QueryException
     * @throws \Throwable
     */
    public function delete(Board $board): void
    {
        try {
            DB::transaction(function () use ($board) {
                // Supprimer les cartes de toutes les colonnes
                foreach ($board->columns as $column) {
                    $column->cards()->delete();
                }

                // Supprimer les colonnes
                $board->columns()->delete();

                // Supprimer le board
                $this->boardRepository->delete($board);
            });
        } catch (\Exception $exception) {
            throw new BoardDeletionFailedException($board->id);
        }

    }
}
