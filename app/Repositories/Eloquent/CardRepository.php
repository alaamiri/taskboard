<?php

namespace App\Repositories\Eloquent;

use App\Models\Card;
use App\Models\Column;
use App\Repositories\Contracts\CardRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class CardRepository implements CardRepositoryInterface
{
    public function findById(int $id): ?Card
    {
        return Cache::remember(
            "card.{$id}",
            now()->addMinutes(30),
            fn () => Card::find($id)
        );
    }

    public function create(array $data): Card
    {
        $card = Card::create($data);

        $this->clearColumnCache($data['column_id']);

        return $card;
    }

    public function update(Card $card, array $data): Card
    {
        $oldColumnId = $card->column_id;

        $card->update($data);

        $this->clearCardCache($card);

        // Si la carte a changé de colonne
        if (isset($data['column_id']) && $data['column_id'] !== $oldColumnId) {
            $this->clearColumnCache($oldColumnId);
        }

        return $card->fresh();
    }

    public function delete(Card $card): void
    {
        $columnId = $card->column_id;
        $cardId = $card->id;

        $card->delete();

        Cache::forget("card.{$cardId}");
        $this->clearColumnCache($columnId);
    }

    private function clearCardCache(Card $card): void
    {
        Cache::forget("card.{$card->id}");
        $this->clearColumnCache($card->column_id);
    }

    private function clearColumnCache(int $columnId): void
    {
        $column = Column::find($columnId);

        if ($column) {
            Cache::forget("column.{$columnId}");
            Cache::forget("board.{$column->board_id}");
            Cache::forget("board.{$column->board_id}.with_relations");
        }
    }
}
