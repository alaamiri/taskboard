<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ColumnResource;
use App\Models\Board;
use App\Models\Column;
use App\Services\ColumnService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ColumnController extends Controller
{
    public function __construct(
        private readonly ColumnService $columnService
    ) {}

    /**
     * POST /api/boards/{board}/columns
     */
    public function store(Request $request, Board $board): ColumnResource
    {
        $this->authorize('create', [Column::class, $board]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $column = $this->columnService->create($board, $validated);

        return new ColumnResource($column);
    }

    /**
     * PUT /api/columns/{column}
     */
    public function update(Request $request, Column $column): ColumnResource
    {
        $this->authorize('update', $column);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'position' => 'sometimes|integer|min:0',
        ]);

        $column = $this->columnService->update($column, $validated);

        return new ColumnResource($column);
    }

    /**
     * DELETE /api/columns/{column}
     */
    public function destroy(Column $column): Response
    {
        $this->authorize('delete', $column);

        $this->columnService->delete($column);

        return response()->noContent();
    }
}
