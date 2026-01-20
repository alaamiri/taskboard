<?php

namespace App\Services;

use App\Data\Card\CardData;
use App\Data\Card\MoveCardData;
use App\Events\CardDeleted;
use App\Events\CardMoved;
use App\Models\Card;
use App\Models\Column;
use App\Repositories\Contracts\CardRepositoryInterface;
use Spatie\LaravelData\Optional;

class CardService
{
    public function __construct(
        private readonly CardRepositoryInterface $cardRepository
    ) {}

    public function create(Column $column, CardData $data): Card
    {
        $position = $column->cards()->count();

        return $this->cardRepository->create([
            'title' => $data->title,
            'description' => $data->description,
            'column_id' => $column->id,
            'position' => $position,
            'user_id' => null,
        ]);
    }

    public function update(Card $card, CardData $data): Card
    {
        $updateData = [];

        if (!$data->title instanceof Optional) {
            $updateData['title'] = $data->title;
        }

        if ($data->description !== null || !$data->title instanceof Optional) {
            $updateData['description'] = $data->description;
        }

        return $this->cardRepository->update($card, $updateData);
    }

    public function delete(Card $card): void
    {
        $boardId = $card->column->board_id;
        $cardId = $card->id;

        $this->cardRepository->delete($card);

        broadcast(new CardDeleted($boardId, $cardId))->toOthers();
    }

    public function move(Card $card, MoveCardData $data): Card
    {
        $targetColumn = Column::find($data->column_id);

        if ($targetColumn->board_id !== $card->column->board_id) {
            abort(403, 'Cannot move card to another board');
        }

        $fromColumnId = $card->column_id;

        $card = $this->cardRepository->update($card, [
            'column_id' => $data->column_id,
            'position' => $data->position,
        ]);

        broadcast(new CardMoved(
            $card,
            $fromColumnId,
            $data->column_id,
            $data->position
        ))->toOthers();

        return $card;
    }
}
