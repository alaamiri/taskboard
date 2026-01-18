<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BoardResource;
use App\Models\Board;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BoardController extends Controller
{
    /**
     * GET /api/boards
     * Liste des boards de l'utilisateur
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $boards = Board::where('user_id', $request->user()->id)
            ->withCount('columns')
            ->latest()
            ->get();

        return BoardResource::collection($boards);
    }

    /**
     * POST /api/boards
     * Créer un board
     */
    public function store(Request $request): BoardResource
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $board = $request->user()->boards()->create($validated);

        return new BoardResource($board);
    }

    /**
     * GET /api/boards/{board}
     * Afficher un board avec ses colonnes et cartes
     */
    public function show(Board $board): BoardResource
    {
        $this->authorize('view', $board);

        $board->load(['columns.cards', 'user']);

        return new BoardResource($board);
    }

    /**
     * PUT /api/boards/{board}
     * Mettre à jour un board
     */
    public function update(Request $request, Board $board): BoardResource
    {
        $this->authorize('update', $board);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $board->update($validated);

        return new BoardResource($board);
    }

    /**
     * DELETE /api/boards/{board}
     * Supprimer un board
     */
    public function destroy(Board $board): Response
    {
        $this->authorize('delete', $board);

        $board->delete();

        return response()->noContent();
    }
}
