<?php

namespace App\Notifications;

use App\Models\Card;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class CardMovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Card $card,
        public readonly User $movedBy,
        public readonly string $fromColumn,
        public readonly string $toColumn
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
            'moved_by_id' => $this->movedBy->id,
            'moved_by_name' => $this->movedBy->name,
            'from_column' => $this->fromColumn,
            'to_column' => $this->toColumn,
            'message' => "{$this->movedBy->name} a déplacé '{$this->card->title}' de '{$this->fromColumn}' vers '{$this->toColumn}'",
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'card_moved',
            'card_id' => $this->card->id,
            'card_title' => $this->card->title,
            'board_id' => $this->card->column->board_id,
            'moved_by' => $this->movedBy->name,
            'from_column' => $this->fromColumn,
            'to_column' => $this->toColumn,
            'message' => "{$this->movedBy->name} a déplacé '{$this->card->title}' de '{$this->fromColumn}' vers '{$this->toColumn}'",
        ]);
    }
}
