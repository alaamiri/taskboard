<?php

namespace App\Events;

use App\Models\Card;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Card $card,
        public int $fromColumnId,
        public int $toColumnId,
        public int $newPosition
    ) {}

    /**
     * Canal sur lequel diffuser l'événement
     * Tous les utilisateurs sur ce board recevront l'événement
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('board.' . $this->card->column->board_id)
        ];
    }

    /**
     * Nom de l'événement côté client
     */
    public function broadcastAs(): string
    {
        return 'card.moved';
    }

    /**
     * Données envoyées au client
     */
    public function broadcastWith(): array
    {
        return [
            'card_id' => $this->card->id,
            'from_column_id' => $this->fromColumnId,
            'to_column_id' => $this->toColumnId,
            'position' => $this->newPosition,
            'card' => [
                'id' => $this->card->id,
                'title' => $this->card->title,
                'description' => $this->card->description,
                'column_id' => $this->card->column_id,
                'position' => $this->card->position
            ]
        ];
    }
}
