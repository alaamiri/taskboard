<?php

namespace App\Events;

use App\Models\Board;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoardUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Board $board
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('board.' . $this->board->id)
        ];
    }

    public function broadcastAs(): string
    {
        return 'board.updated';
    }
}
