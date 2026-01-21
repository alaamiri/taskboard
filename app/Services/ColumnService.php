<?php

namespace App\Services;

use App\Data\Column\ColumnData;
use App\Events\ColumnDeleted;
use App\Models\Board;
use App\Models\Column;
use App\Repositories\Contracts\ColumnRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class ColumnService
{
    public function __construct(
        private readonly ColumnRepositoryInterface $columnRepository
    ) {}

    public function create(Board $board, ColumnData $data): Column
    {
        $position = $board->columns()->count();

        return $this->columnRepository->create([
            'name' => $data->name,
            'board_id' => $board->id,
            'position' => $position,
        ]);
    }

    public function update(Column $column, ColumnData $data): Column
    {
        $updateData = [];

        if (!$data->name instanceof Optional) {
            $updateData['name'] = $data->name;
        }

        if ($data->position !== null) {
            $updateData['position'] = $data->position;
        }

        return $this->columnRepository->update($column, $updateData);
    }

    public function delete(Column $column): void
    {
        $boardId = $column->board_id;
        $columnId = $column->id;

        DB::transaction(function () use ($column) {
            $column->cards()->delete();
            $this->columnRepository->delete($column);
        });

        broadcast(new ColumnDeleted($boardId, $columnId))->toOthers();
    }
}
