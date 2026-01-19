<?php

namespace App\Services;

use App\Events\CardDeleted;
use App\Events\CardMoved;
use App\Models\Card;
use App\Models\Column;
use App\Repositories\Contracts\CardRepositoryInterface;

class CardService
{
    public function __construct(
        private readonly CardRepositoryInterface $cardRepository
    ) {}

    public function create(Column $column, array $data): Card
    {
        $position = $column->cards()->count();

        return $this->cardRepository->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'column_id' => $column->id,
            'position' => $position,
            'user_id' => null,
        ]);
    }

    public function update(Card $card, array $data): Card
    {
        return $this->cardRepository->update($card, $data);
    }

    public function delete(Card $card): void
    {
        $boardId = $card->column->board_id;
        $cardId = $card->id;

        $this->cardRepository->delete($card);

        broadcast(new CardDeleted($boardId, $cardId))->toOthers();
    }

    public function move(Card $card, array $data): Card
    {
        $targetColumn = Column::find($data['column_id']);

        if ($targetColumn->board_id !== $card->column->board_id) {
            abort(403, 'Cannot move card to another board');
        }

        $fromColumnId = $card->column_id;

        $card = $this->cardRepository->update($card, [
            'column_id' => $data['column_id'],
            'position' => $data['position'],
        ]);

        broadcast(new CardMoved(
            $card,
            $fromColumnId,
            $data['column_id'],
            $data['position']
        ))->toOthers();

        return $card;
    }
}
