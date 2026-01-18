<?php

namespace App\Http\Controllers;

use App\Events\ColumnDeleted;
use App\Models\Board;
use App\Models\Column;
use Illuminate\Http\Request;

class ColumnController extends Controller
{
    public function store(Request $request, Board $board)
    {
        $this->authorize('create', [Column::class, $board]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $position = $board->columns()->count();

        $board->columns()->create([
            'name' => $validated['name'],
            'position' => $position
        ]);

        return redirect()->back()->with('success', 'Colonne créée !');
    }

    public function update(Request $request, Column $column)
    {
        $this->authorize('update', $column);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'position' => 'sometimes|integer|min:0'
        ]);

        $column->update($validated);

        return redirect()->back()->with('success', 'Colonne mise à jour !');
    }

    public function destroy(Column $column)
    {
        $this->authorize('delete', $column);

        $boardId = $column->board_id;
        $columnId = $column->id;

        $column->delete();

        broadcast(new ColumnDeleted($boardId, $columnId))->toOthers();

        return redirect()->back()->with('success', 'Colonne supprimée !');
    }
}
