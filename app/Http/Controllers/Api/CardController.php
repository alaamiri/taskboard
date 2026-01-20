<?php

namespace App\Http\Controllers\Api;

use App\Data\Card\CardData;
use App\Data\Card\MoveCardData;
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

    public function store(Request $request, Column $column): CardResource
    {
        $this->authorize('create', [Card::class, $column]);

        $data = CardData::from($request);

        $card = $this->cardService->create($column, $data);

        return new CardResource($card);
    }

    public function update(Request $request, Card $card): CardResource
    {
        $this->authorize('update', $card);

        $data = CardData::from($request);

        $card = $this->cardService->update($card, $data);

        return new CardResource($card);
    }

    public function destroy(Card $card): Response
    {
        $this->authorize('delete', $card);

        $this->cardService->delete($card);

        return response()->noContent();
    }

    public function move(Request $request, Card $card): CardResource
    {
        $this->authorize('move', $card);

        $data = MoveCardData::from($request);

        $card = $this->cardService->move($card, $data);

        return new CardResource($card);
    }
}
