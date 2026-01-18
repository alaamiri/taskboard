<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $boardId,
        public int $cardId
    )
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('board.' . $this->boardId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'card.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            // Identifiants
            'board_id' => $this->boardId,
            'card_id' => $this->cardId,

            // Métadonnées (utile pour logs/audit)
            'action' => 'deleted',
            'timestamp' => now()->toISOString(),
            'triggered_by' => auth()->id() ?? null,
        ];
    }
}
