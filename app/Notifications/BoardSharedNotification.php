<?php

namespace App\Notifications;

use App\Models\Board;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class BoardSharedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Board $board,
        public readonly User $sharedBy
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'board_id' => $this->board->id,
            'board_name' => $this->board->name,
            'shared_by_id' => $this->sharedBy->id,
            'shared_by_name' => $this->sharedBy->name,
            'message' => "{$this->sharedBy->name} a partagé le board '{$this->board->name}' avec vous",
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'board_shared',
            'board_id' => $this->board->id,
            'board_name' => $this->board->name,
            'shared_by' => $this->sharedBy->name,
            'message' => "{$this->sharedBy->name} a partagé le board '{$this->board->name}' avec vous",
        ]);
    }
}
