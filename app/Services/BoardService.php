<?php

namespace App\Services;

use App\Data\Board\BoardData;
use App\Models\Board;
use App\Models\User;
use App\Repositories\Contracts\BoardRepositoryInterface;
use Illuminate\Support\Collection;
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

    public function create(User $user, BoardData $data): Board
    {
        return $this->boardRepository->create([
            'name' => $data->name,
            'description' => $data->description,
            'user_id' => $user->id,
        ]);
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

    public function delete(Board $board): void
    {
        $this->boardRepository->delete($board);
    }
}
