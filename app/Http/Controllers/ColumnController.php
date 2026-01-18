<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Column;
use App\Events\ColumnDeleted;
use Illuminate\Http\Request;

class ColumnController extends Controller
{
    /**
     * Crée une nouvelle colonne dans un board
     */
    public function store(Request $request, Board $board)
    {
        // Vérifie que l'utilisateur possède ce board
        if ($board->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Calcule la position (à la fin)
        $position = $board->columns()->count();

        $board->columns()->create([
            'name' => $validated['name'],
            'position' => $position
        ]);

        return redirect()->back()->with('success', 'Colonne créée !');
    }

    /**
     * Met à jour une colonne (nom ou position)
     */
    public function update(Request $request, Column $column)
    {
        // Vérifie via la relation : column -> board -> user
        if ($column->board->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'position' => 'sometimes|integer|min:0'
        ]);

        $column->update($validated);

        return redirect()->back()->with('success', 'Colonne mise à jour !');
    }

    /**
     * Supprime une colonne et toutes ses cartes
     */
    public function destroy(Column $column)
    {
        if ($column->board->user_id !== auth()->id()) {
            abort(403);
        }

        $boardId = $column->board_id;
        $columnId = $column->id;

        $column->delete();

        broadcast(new ColumnDeleted($boardId, $columnId))->toOthers();

        return redirect()->back()->with('success', 'Colonne supprimée !');
    }
}
