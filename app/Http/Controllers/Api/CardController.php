<?php

namespace App\Http\Controllers\Api;

use App\Data\Card\CardData;
use App\Data\Card\MoveCardData;
use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Models\Card;
use App\Models\Column;
use App\Services\Model\CardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group Cards
 *
 * APIs for managing cards within columns
 */
class CardController extends Controller
{
    public function __construct(
        private readonly CardService $cardService
    ) {}

    /**
     * List cards in a column
     *
     * Returns all cards in the specified column, ordered by position.
     *
     * @authenticated
     *
     * @urlParam column integer required The column ID. Example: 1
     *
     * @response 200 scenario="success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Task 1",
     *       "description": "First task description",
     *       "position": 0,
     *       "column_id": 1,
     *       "user_id": 1,
     *       "created_at": "2026-01-25T10:00:00.000000Z",
     *       "updated_at": "2026-01-25T10:00:00.000000Z"
     *     }
     *   ]
     * }
     * @response 403 scenario="forbidden" {"message": "This action is unauthorized."}
     * @response 404 scenario="not found" {"message": "Column not found."}
     */
    public function index(Column $column): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Card::class, $column]);

        $cards = $column->cards()->with('user')->orderBy('position')->get();

        return CardResource::collection($cards);
    }

    /**
     * Get a card
     *
     * Returns a single card with its associated user.
     *
     * @authenticated
     *
     * @urlParam card integer required The card ID. Example: 1
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "title": "Task 1",
     *     "description": "First task description",
     *     "position": 0,
     *     "column_id": 1,
     *     "user_id": 1,
     *     "created_at": "2026-01-25T10:00:00.000000Z",
     *     "updated_at": "2026-01-25T10:00:00.000000Z",
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe"
     *     }
     *   }
     * }
     * @response 403 scenario="forbidden" {"message": "This action is unauthorized."}
     * @response 404 scenario="not found" {"message": "Card not found."}
     */
    public function show(Card $card): CardResource
    {
        $this->authorize('view', $card);

        $card->load('user');

        return new CardResource($card);
    }

    /**
     * Create a card
     *
     * Creates a new card in the specified column. Position is auto-assigned.
     *
     * @authenticated
     *
     * @urlParam column integer required The column ID. Example: 1
     * @bodyParam title string required The title of the card. Example: New Task
     * @bodyParam description string optional The card description. Example: Task details here
     *
     * @response 201 scenario="created" {
     *   "data": {
     *     "id": 2,
     *     "title": "New Task",
     *     "description": "Task details here",
     *     "position": 1,
     *     "column_id": 1,
     *     "user_id": 1,
     *     "created_at": "2026-01-25T10:00:00.000000Z",
     *     "updated_at": "2026-01-25T10:00:00.000000Z"
     *   }
     * }
     * @response 403 scenario="forbidden" {"message": "This action is unauthorized."}
     * @response 422 scenario="validation error" {"message": "The title field is required.", "errors": {"title": ["The title field is required."]}}
     */
    public function store(Request $request, Column $column): CardResource
    {
        $this->authorize('create', [Card::class, $column]);

        $data = CardData::from($request);

        $card = $this->cardService->create($column, $data);

        return new CardResource($card);
    }

    /**
     * Update a card
     *
     * Updates the card title and/or description.
     *
     * @authenticated
     *
     * @urlParam card integer required The card ID. Example: 1
     * @bodyParam title string The new title for the card. Example: Updated Task
     * @bodyParam description string The new description. Example: Updated description
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "title": "Updated Task",
     *     "description": "Updated description",
     *     "position": 0,
     *     "column_id": 1,
     *     "user_id": 1,
     *     "created_at": "2026-01-25T10:00:00.000000Z",
     *     "updated_at": "2026-01-25T11:00:00.000000Z"
     *   }
     * }
     * @response 403 scenario="forbidden" {"message": "This action is unauthorized."}
     */
    public function update(Request $request, Card $card): CardResource
    {
        $this->authorize('update', $card);

        $data = CardData::from($request);

        $card = $this->cardService->update($card, $data);

        return new CardResource($card);
    }

    /**
     * Delete a card
     *
     * Deletes the card permanently.
     *
     * @authenticated
     *
     * @urlParam card integer required The card ID. Example: 1
     *
     * @response 204 scenario="deleted"
     * @response 403 scenario="forbidden" {"message": "This action is unauthorized."}
     */
    public function destroy(Card $card): Response
    {
        $this->authorize('delete', $card);

        $this->cardService->delete($card);

        return response()->noContent();
    }

    /**
     * Move a card
     *
     * Moves a card to a different column and/or position. Triggers notifications to board owner.
     *
     * @authenticated
     *
     * @urlParam card integer required The card ID. Example: 1
     * @bodyParam column_id integer required The target column ID. Example: 2
     * @bodyParam position integer required The new position (0-based index). Example: 0
     *
     * @response 200 scenario="success" {
     *   "data": {
     *     "id": 1,
     *     "title": "Task 1",
     *     "description": "First task description",
     *     "position": 0,
     *     "column_id": 2,
     *     "user_id": 1,
     *     "created_at": "2026-01-25T10:00:00.000000Z",
     *     "updated_at": "2026-01-25T11:00:00.000000Z"
     *   }
     * }
     * @response 403 scenario="forbidden" {"message": "This action is unauthorized."}
     * @response 422 scenario="validation error" {"message": "The column_id field is required.", "errors": {"column_id": ["The column_id field is required."]}}
     */
    public function move(Request $request, Card $card): CardResource
    {
        $this->authorize('move', $card);

        $data = MoveCardData::from($request);

        $card = $this->cardService->move($card, $data);

        return new CardResource($card);
    }
}
