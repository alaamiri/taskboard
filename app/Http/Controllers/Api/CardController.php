<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Models\Card;
use App\Models\Column;
use App\Services\CardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group Cards
 *
 * Gestion des cartes (tâches) dans les colonnes
 */
class CardController extends Controller
{
    public function __construct(
        private readonly CardService $cardService
    ) {}

    /**
     * Créer une carte
     *
     * Ajoute une nouvelle carte à une colonne.
     *
     * @authenticated
     *
     * @urlParam column integer required L'ID de la colonne. Example: 1
     *
     * @bodyParam title string required Le titre de la carte. Example: Implémenter l'API
     * @bodyParam description string La description de la carte. Example: Créer les endpoints REST
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "title": "Implémenter l'API",
     *     "description": "Créer les endpoints REST",
     *     "position": 0,
     *     "column_id": 1,
     *     "created_at": "2024-01-15T10:30:00.000000Z",
     *     "updated_at": "2024-01-15T10:30:00.000000Z"
     *   }
     * }
     */
    public function store(Request $request, Column $column): CardResource
    {
        $this->authorize('create', [Card::class, $column]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $card = $this->cardService->create($column, $validated);

        return new CardResource($card);
    }

    /**
     * Modifier une carte
     *
     * Met à jour le titre ou la description d'une carte.
     *
     * @authenticated
     *
     * @urlParam card integer required L'ID de la carte. Example: 1
     *
     * @bodyParam title string Le nouveau titre. Example: Nouveau titre
     * @bodyParam description string La nouvelle description. Example: Nouvelle description
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "title": "Nouveau titre",
     *     "description": "Nouvelle description",
     *     "position": 0,
     *     "column_id": 1,
     *     "created_at": "2024-01-15T10:30:00.000000Z",
     *     "updated_at": "2024-01-15T11:00:00.000000Z"
     *   }
     * }
     */
    public function update(Request $request, Card $card): CardResource
    {
        $this->authorize('update', $card);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $card = $this->cardService->update($card, $validated);

        return new CardResource($card);
    }

    /**
     * Supprimer une carte
     *
     * Supprime définitivement une carte.
     *
     * @authenticated
     *
     * @urlParam card integer required L'ID de la carte. Example: 1
     *
     * @response 204 scenario="success"
     */
    public function destroy(Card $card): Response
    {
        $this->authorize('delete', $card);

        $this->cardService->delete($card);

        return response()->noContent();
    }

    /**
     * Déplacer une carte
     *
     * Déplace une carte vers une autre colonne et/ou position.
     * Déclenche un événement WebSocket pour la synchronisation temps réel.
     *
     * @authenticated
     *
     * @urlParam card integer required L'ID de la carte. Example: 1
     *
     * @bodyParam column_id integer required L'ID de la colonne de destination. Example: 2
     * @bodyParam position integer required La nouvelle position dans la colonne. Example: 0
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "title": "Ma carte",
     *     "description": "Description",
     *     "position": 0,
     *     "column_id": 2,
     *     "created_at": "2024-01-15T10:30:00.000000Z",
     *     "updated_at": "2024-01-15T11:00:00.000000Z"
     *   }
     * }
     *
     * @response 403 scenario="autre board" {
     *   "message": "Cannot move card to another board"
     * }
     */
    public function move(Request $request, Card $card): CardResource
    {
        $this->authorize('move', $card);

        $validated = $request->validate([
            'column_id' => 'required|exists:columns,id',
            'position' => 'required|integer|min:0',
        ]);

        $card = $this->cardService->move($card, $validated);

        return new CardResource($card);
    }
}
