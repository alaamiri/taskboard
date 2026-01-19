<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ColumnResource;
use App\Models\Board;
use App\Models\Column;
use App\Services\ColumnService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group Columns
 *
 * Gestion des colonnes dans un board
 */
class ColumnController extends Controller
{
    public function __construct(
        private readonly ColumnService $columnService
    ) {}

    /**
     * Créer une colonne
     *
     * Ajoute une nouvelle colonne à un board.
     *
     * @authenticated
     *
     * @urlParam board integer required L'ID du board. Example: 1
     *
     * @bodyParam name string required Le nom de la colonne. Example: To Do
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "name": "To Do",
     *     "position": 0,
     *     "board_id": 1,
     *     "created_at": "2024-01-15T10:30:00.000000Z",
     *     "updated_at": "2024-01-15T10:30:00.000000Z"
     *   }
     * }
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
     * Modifier une colonne
     *
     * Met à jour le nom ou la position d'une colonne.
     *
     * @authenticated
     *
     * @urlParam column integer required L'ID de la colonne. Example: 1
     *
     * @bodyParam name string Le nouveau nom. Example: In Progress
     * @bodyParam position integer La nouvelle position. Example: 1
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "name": "In Progress",
     *     "position": 1,
     *     "board_id": 1,
     *     "created_at": "2024-01-15T10:30:00.000000Z",
     *     "updated_at": "2024-01-15T11:00:00.000000Z"
     *   }
     * }
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
     * Supprimer une colonne
     *
     * Supprime une colonne et toutes ses cartes.
     *
     * @authenticated
     *
     * @urlParam column integer required L'ID de la colonne. Example: 1
     *
     * @response 204 scenario="success"
     */
    public function destroy(Column $column): Response
    {
        $this->authorize('delete', $column);

        $this->columnService->delete($column);

        return response()->noContent();
    }
}
