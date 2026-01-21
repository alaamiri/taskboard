<?php

namespace App\Http\Controllers\Api;

use App\Data\Column\ColumnData;
use App\Http\Controllers\Controller;
use App\Http\Resources\ColumnResource;
use App\Models\Board;
use App\Models\Column;
use App\Services\Model\ColumnService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ColumnController extends Controller
{
    public function __construct(
        private readonly ColumnService $columnService
    ) {}

    public function store(Request $request, Board $board): ColumnResource
    {
        $this->authorize('create', [Column::class, $board]);

        $data = ColumnData::from($request);

        $column = $this->columnService->create($board, $data);

        return new ColumnResource($column);
    }

    public function update(Request $request, Column $column): ColumnResource
    {
        $this->authorize('update', $column);

        $data = ColumnData::from($request);

        $column = $this->columnService->update($column, $data);

        return new ColumnResource($column);
    }

    public function destroy(Column $column): Response
    {
        $this->authorize('delete', $column);

        $this->columnService->delete($column);

        return response()->noContent();
    }
}
