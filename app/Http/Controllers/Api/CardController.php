<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Events\CardDeleted;
use App\Events\CardMoved;
use App\Models\Card;
use App\Models\Column;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CardController extends Controller
{
    public function store(Request $request, Column $column): CardResource
    {
        $this->authorize('create', [Card::class, $column]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $position = $column->cards()->count();

        $card = $column->cards()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'position' => $position,
            'user_id' => null,
        ]);

        return new CardResource($card);
    }

    public function update(Request $request, Card $card): CardResource
    {
        $this->authorize('update', $card);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $card->update($validated);

        return new CardResource($card);
    }

    public function destroy(Card $card): Response
    {
        $this->authorize('delete', $card);

        $boardId = $card->column->board_id;
        $cardId = $card->id;

        $card->delete();

        broadcast(new CardDeleted($boardId, $cardId))->toOthers();

        return response()->noContent();
    }

    public function move(Request $request, Card $card): CardResource
    {
        $this->authorize('move', $card);

        $validated = $request->validate([
            'column_id' => 'required|exists:columns,id',
            'position' => 'required|integer|min:0',
        ]);

        $targetColumn = Column::find($validated['column_id']);

        if ($targetColumn->board_id !== $card->column->board_id) {
            abort(403, 'Cannot move to another board');
        }

        $fromColumnId = $card->column_id;

        $card->update([
            'column_id' => $validated['column_id'],
            'position' => $validated['position'],
        ]);

        broadcast(new CardMoved(
            $card,
            $fromColumnId,
            $validated['column_id'],
            $validated['position']
        ))->toOthers();

        return new CardResource($card);
    }
}
