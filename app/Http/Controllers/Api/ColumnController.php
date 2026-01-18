<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ColumnResource;
use App\Models\Board;
use App\Models\Column;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ColumnController extends Controller
{
    public function store(Request $request, Board $board): ColumnResource
    {
        $this->authorize('create', [Column::class, $board]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $position = $board->columns()->count();

        $column = $board->columns()->create([
            'name' => $validated['name'],
            'position' => $position,
        ]);

        return new ColumnResource($column);
    }

    public function update(Request $request, Column $column): ColumnResource
    {
        $this->authorize('update', $column);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'position' => 'sometimes|integer|min:0',
        ]);

        $column->update($validated);

        return new ColumnResource($column);
    }

    public function destroy(Column $column): Response
    {
        $this->authorize('delete', $column);

        $column->delete();

        return response()->noContent();
    }
}
