<?php

namespace App\Notifications;

use App\Models\Card;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class CardAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Card $card,
        public readonly User $assignedBy
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'card_id' => $this->card->id,
            'card_title' => $this->card->title,
            'board_id' => $this->card->column->board_id,
            'board_name' => $this->card->column->board->name,
            'assigned_by_id' => $this->assignedBy->id,
            'assigned_by_name' => $this->assignedBy->name,
            'message' => "{$this->assignedBy->name} vous a assigné à la carte '{$this->card->title}'",
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'card_assigned',
            'card_id' => $this->card->id,
            'card_title' => $this->card->title,
            'board_id' => $this->card->column->board_id,
            'assigned_by' => $this->assignedBy->name,
            'message' => "{$this->assignedBy->name} vous a assigné à la carte '{$this->card->title}'",
        ]);
    }
}
