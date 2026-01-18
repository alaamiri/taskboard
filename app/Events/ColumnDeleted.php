<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ColumnDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $boardId,
        public int $columnId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('board.' . $this->boardId)
        ];
    }

    public function broadcastAs(): string
    {
        return 'column.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'board_id' => $this->boardId,
            'column_id' => $this->columnId,
            'action' => 'deleted',
            'timestamp' => now()->toISOString()
        ];
    }
}
