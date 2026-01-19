<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BoardResource;
use App\Models\Board;
use App\Services\BoardService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @group Boards
 *
 * Gestion des boards (tableaux Kanban)
 */
class BoardController extends Controller
{
    public function __construct(
        private readonly BoardService $boardService
    ) {}

    /**
     * Liste des boards
     *
     * Récupère tous les boards de l'utilisateur connecté.
     *
     * @authenticated
     *
     * @response 200 scenario="success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Mon Projet",
     *       "description": "Description du projet",
     *       "created_at": "2024-01-15T10:30:00.000000Z",
     *       "updated_at": "2024-01-15T10:30:00.000000Z",
     *       "columns_count": 3
     *     }
     *   ]
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $boards = $this->boardService->getAllForUser($request->user());

        return BoardResource::collection($boards);
    }

    /**
     * Créer un board
     *
     * Crée un nouveau board pour l'utilisateur connecté.
     *
     * @authenticated
     *
     * @bodyParam name string required Le nom du board. Example: Mon Projet
     * @bodyParam description string La description du board. Example: Description de mon projet
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "name": "Mon Projet",
     *     "description": "Description de mon projet",
     *     "created_at": "2024-01-15T10:30:00.000000Z",
     *     "updated_at": "2024-01-15T10:30:00.000000Z"
     *   }
     * }
     *
     * @response 422 scenario="validation error" {
     *   "message": "The name field is required.",
     *   "errors": {
     *     "name": ["The name field is required."]
     *   }
     * }
     */
    public function store(Request $request): BoardResource
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $board = $this->boardService->create($request->user(), $validated);

        return new BoardResource($board);
    }

    /**
     * Afficher un board
     *
     * Récupère un board avec ses colonnes et cartes.
     *
     * @authenticated
     *
     * @urlParam board integer required L'ID du board. Example: 1
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "name": "Mon Projet",
     *     "description": "Description",
     *     "created_at": "2024-01-15T10:30:00.000000Z",
     *     "updated_at": "2024-01-15T10:30:00.000000Z",
     *     "columns": [
     *       {
     *         "id": 1,
     *         "name": "To Do",
     *         "position": 0,
     *         "cards": []
     *       }
     *     ]
     *   }
     * }
     *
     * @response 403 scenario="forbidden" {
     *   "message": "This action is unauthorized."
     * }
     *
     * @response 404 scenario="not found" {
     *   "message": "No query results for model [App\\Models\\Board] 999"
     * }
     */
    public function show(Board $board): BoardResource
    {
        $this->authorize('view', $board);

        $board = $this->boardService->getWithRelations($board);

        return new BoardResource($board);
    }

    /**
     * Modifier un board
     *
     * Met à jour les informations d'un board.
     *
     * @authenticated
     *
     * @urlParam board integer required L'ID du board. Example: 1
     *
     * @bodyParam name string Le nouveau nom du board. Example: Nouveau Nom
     * @bodyParam description string La nouvelle description. Example: Nouvelle description
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "name": "Nouveau Nom",
     *     "description": "Nouvelle description",
     *     "created_at": "2024-01-15T10:30:00.000000Z",
     *     "updated_at": "2024-01-15T11:00:00.000000Z"
     *   }
     * }
     */
    public function update(Request $request, Board $board): BoardResource
    {
        $this->authorize('update', $board);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $board = $this->boardService->update($board, $validated);

        return new BoardResource($board);
    }

    /**
     * Supprimer un board
     *
     * Supprime un board et toutes ses colonnes et cartes.
     *
     * @authenticated
     *
     * @urlParam board integer required L'ID du board. Example: 1
     *
     * @response 204 scenario="success"
     *
     * @response 403 scenario="forbidden" {
     *   "message": "This action is unauthorized."
     * }
     */
    public function destroy(Board $board): Response
    {
        $this->authorize('delete', $board);

        $this->boardService->delete($board);

        return response()->noContent();
    }
}
