<?php

namespace App\Services;

use App\Events\ColumnDeleted;
use App\Models\Board;
use App\Models\Column;
use App\Repositories\Contracts\ColumnRepositoryInterface;

class ColumnService
{
    public function __construct(
        private readonly ColumnRepositoryInterface $columnRepository
    ) {}

    public function create(Board $board, array $data): Column
    {
        $position = $board->columns()->count();

        return $this->columnRepository->create([
            'name' => $data['name'],
            'board_id' => $board->id,
            'position' => $position,
        ]);
    }

    public function update(Column $column, array $data): Column
    {
        return $this->columnRepository->update($column, $data);
    }

    public function delete(Column $column): void
    {
        $boardId = $column->board_id;
        $columnId = $column->id;

        $this->columnRepository->delete($column);

        broadcast(new ColumnDeleted($boardId, $columnId))->toOthers();
    }
}
