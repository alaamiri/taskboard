<?php

namespace App\Repositories\Eloquent;

use App\Models\Board;
use App\Models\User;
use App\Repositories\Contracts\BoardRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BoardRepository implements BoardRepositoryInterface
{
    public function findById(int $id): ?Board
    {
        return Cache::tags(['boards', "board.{$id}"])
            ->remember(
                "board.{$id}",
                now()->addMinutes(30),
                fn () => Board::find($id)
            );
    }

    public function findByIdWithRelations(int $id): ?Board
    {
        return Cache::tags(['boards', "board.{$id}"])
            ->remember(
                "board.{$id}.with_relations",
                now()->addMinutes(30),
                fn () => Board::with(['columns.cards', 'user'])->find($id)
            );
    }

    public function getAllForUser(User $user): Collection
    {
        return Cache::tags(['boards', "user.{$user->id}.boards"])
            ->remember(
                "user.{$user->id}.boards",
                now()->addMinutes(10),
                fn () => Board::where('user_id', $user->id)
                    ->withCount('columns')
                    ->latest()
                    ->get()
            );
    }

    public function create(array $data): Board
    {
        $board = Board::create($data);

        // Invalide tous les caches liés à l'utilisateur
        Cache::tags(["user.{$data['user_id']}.boards"])->flush();

        return $board;
    }

    public function update(Board $board, array $data): Board
    {
        $board->update($data);

        // Invalide les caches du board
        Cache::tags(["board.{$board->id}"])->flush();
        Cache::tags(["user.{$board->user_id}.boards"])->flush();

        return $board->fresh();
    }

    public function delete(Board $board): void
    {
        $userId = $board->user_id;
        $boardId = $board->id;

        $board->delete();

        // Invalide les caches
        Cache::tags(["board.{$boardId}"])->flush();
        Cache::tags(["user.{$userId}.boards"])->flush();
    }

    public function getAll(): Collection
    {
        return Cache::remember(
            'boards.all',
            now()->addMinutes(10),
            fn () => Board::withCount('columns')
                ->latest()
                ->get()
        );
    }

    private function clearUserCache(int $userId): void
    {
        Cache::forget("user.{$userId}.boards");
        Cache::forget('boards.all');
    }
}
