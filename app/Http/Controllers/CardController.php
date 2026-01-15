<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Column;
use Illuminate\Http\Request;

class CardController extends Controller
{
    /**
     * Crée une nouvelle carte dans une colonne
     */
    public function store(Request $request, Column $column)
    {
        // Vérifie : column -> board -> user
        if ($column->board->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        // Position à la fin de la colonne
        $position = $column->cards()->count();

        $column->cards()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'position' => $position,
            'user_id' => null  // Pas assignée par défaut
        ]);

        return redirect()->back()->with('success', 'Carte créée !');
    }

    /**
     * Met à jour une carte
     */
    public function update(Request $request, Card $card)
    {
        if ($card->column->board->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'position' => 'sometimes|integer|min:0',
            'column_id' => 'sometimes|exists:columns,id',
            'user_id' => 'nullable|exists:users,id'
        ]);

        // Si on change de colonne, vérifie que la nouvelle colonne appartient au même user
        if (isset($validated['column_id'])) {
            $newColumn = Column::find($validated['column_id']);
            if ($newColumn->board->user_id !== auth()->id()) {
                abort(403);
            }
        }

        $card->update($validated);

        return redirect()->back()->with('success', 'Carte mise à jour !');
    }

    /**
     * Supprime une carte
     */
    public function destroy(Card $card)
    {
        if ($card->column->board->user_id !== auth()->id()) {
            abort(403);
        }

        $card->delete();

        return redirect()->back()->with('success', 'Carte supprimée !');
    }

    /**
     * Déplace une carte vers une autre colonne (pour le drag & drop)
     */
    public function move(Request $request, Card $card)
    {
        if ($card->column->board->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'column_id' => 'required|exists:columns,id',
            'position' => 'required|integer|min:0'
        ]);

        // Vérifie que la colonne cible appartient au même board
        $targetColumn = Column::find($validated['column_id']);
        if ($targetColumn->board_id !== $card->column->board_id) {
            abort(403, 'Impossible de déplacer vers un autre board');
        }

        $card->update([
            'column_id' => $validated['column_id'],
            'position' => $validated['position']
        ]);

        return redirect()->back()->with('success', 'Carte déplacée !');
    }
}
