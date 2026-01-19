<?php

namespace App\Repositories\Eloquent;

use App\Models\Column;
use App\Repositories\Contracts\ColumnRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class ColumnRepository implements ColumnRepositoryInterface
{
    public function findById(int $id): ?Column
    {
        return Cache::remember(
            "column.{$id}",
            now()->addMinutes(30),
            fn () => Column::find($id)
        );
    }

    public function create(array $data): Column
    {
        $column = Column::create($data);

        $this->clearBoardCache($data['board_id']);

        return $column;
    }

    public function update(Column $column, array $data): Column
    {
        $column->update($data);

        $this->clearColumnCache($column);

        return $column->fresh();
    }

    public function delete(Column $column): void
    {
        $boardId = $column->board_id;
        $columnId = $column->id;

        $column->delete();

        Cache::forget("column.{$columnId}");
        $this->clearBoardCache($boardId);
    }

    private function clearColumnCache(Column $column): void
    {
        Cache::forget("column.{$column->id}");
        $this->clearBoardCache($column->board_id);
    }

    private function clearBoardCache(int $boardId): void
    {
        Cache::forget("board.{$boardId}");
        Cache::forget("board.{$boardId}.with_relations");
    }
}
