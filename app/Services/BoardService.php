<?php

namespace App\Services;

use App\Models\Board;
use App\Models\User;
use App\Repositories\Contracts\BoardRepositoryInterface;
use Illuminate\Support\Collection;

class BoardService
{
    public function __construct(
        private BoardRepositoryInterface $boardRepository
    ) {}

    public function getAllForUser(User $user): Collection
    {
        return $this->boardRepository->getAllForUser($user);
    }

    public function getWithRelations(Board $board): Board
    {
        return $this->boardRepository->findByIdWithRelations($board->id);
    }

    public function create(User $user, array $data): Board
    {
        return $this->boardRepository->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'user_id' => $user->id,
        ]);
    }

    public function update(Board $board, array $data): Board
    {
        return $this->boardRepository->update($board, [
            'name' => $data['name'] ?? $board->name,
            'description' => $data['description'] ?? $board->description,
        ]);
    }

    public function delete(Board $board): void
    {
        $this->boardRepository->delete($board);
    }
}
