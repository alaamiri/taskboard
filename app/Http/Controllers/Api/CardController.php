<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Models\Card;
use App\Models\Column;
use App\Services\CardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CardController extends Controller
{
    public function __construct(
        private readonly CardService $cardService
    ) {}

    /**
     * POST /api/columns/{column}/cards
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
     * PUT /api/cards/{card}
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
     * DELETE /api/cards/{card}
     */
    public function destroy(Card $card): Response
    {
        $this->authorize('delete', $card);

        $this->cardService->delete($card);

        return response()->noContent();
    }

    /**
     * PATCH /api/cards/{card}/move
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
