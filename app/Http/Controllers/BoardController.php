<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BoardController extends Controller
{
    /**
     * Liste tous les boards de l'utilisateur connecté
     */
    public function index()
    {
        // Récupère les boards de l'utilisateur connecté
        // with('columns.cards') = charge aussi les colonnes et cartes (eager loading)
        $boards = Board::where('user_id', auth()->id())
            ->with('columns.cards')
            ->get();

        // Retourne la page React avec les données
        return Inertia::render('Boards/Index', [
            'boards' => $boards
        ]);
    }

    /**
     * Affiche le formulaire de création
     */
    public function create()
    {
        return Inertia::render('Boards/Create');
    }

    /**
     * Enregistre un nouveau board
     */
    public function store(Request $request)
    {
        // Valide les données AVANT d'insérer
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        // Crée le board pour l'utilisateur connecté
        $board = auth()->user()->boards()->create($validated);

        // Redirige vers le board avec un message
        return redirect()->route('boards.show', $board)
            ->with('success', 'Board créé !');
    }

    /**
     * Affiche un board avec ses colonnes et cartes
     */
    public function show(Board $board)
    {
        // Vérifie que l'utilisateur est bien le propriétaire
        if ($board->user_id !== auth()->id()) {
            abort(403, 'Accès interdit');
        }

        // Charge les colonnes et cartes
        $board->load('columns.cards');

        return Inertia::render('Boards/Show', [
            'board' => $board
        ]);
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit(Board $board)
    {
        if ($board->user_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('Boards/Edit', [
            'board' => $board
        ]);
    }

    /**
     * Met à jour un board
     */
    public function update(Request $request, Board $board)
    {
        if ($board->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $board->update($validated);

        return redirect()->route('boards.show', $board)
            ->with('success', 'Board mis à jour !');
    }

    /**
     * Supprime un board
     */
    public function destroy(Board $board)
    {
        if ($board->user_id !== auth()->id()) {
            abort(403);
        }

        $board->delete();

        return redirect()->route('boards.index')
            ->with('success', 'Board supprimé !');
    }
}
