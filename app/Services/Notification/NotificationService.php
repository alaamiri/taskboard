<?php

namespace App\Services\Notification;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use App\Notifications\BoardSharedNotification;
use App\Notifications\CardAssignedNotification;
use App\Notifications\CardMovedNotification;

class NotificationService
{
    public function notifyCardMoved(Card $card, User $movedBy, string $fromColumn, string $toColumn): void
    {
        $usersToNotify = $this->getUsersToNotifyForBoard($card->column->board, $movedBy);

        foreach ($usersToNotify as $user) {
            $user->notify(new CardMovedNotification($card, $movedBy, $fromColumn, $toColumn));
        }
    }

    public function notifyCardAssigned(Card $card, User $assignedTo, User $assignedBy): void
    {
        if ($assignedTo->id !== $assignedBy->id) {
            $assignedTo->notify(new CardAssignedNotification($card, $assignedBy));
        }
    }

    public function notifyBoardShared(Board $board, User $sharedWith, User $sharedBy): void
    {
        if ($sharedWith->id !== $sharedBy->id) {
            $sharedWith->notify(new BoardSharedNotification($board, $sharedBy));
        }
    }

    public function notifyColumnDeleted(Column $column, User $deletedBy): void
    {
        $usersToNotify = $this->getUsersToNotifyForBoard($column->board, $deletedBy);

        foreach ($usersToNotify as $user) {
            // Tu peux créer une ColumnDeletedNotification si nécessaire
        }
    }

    public function notifyBoardDeleted(Board $board, User $deletedBy): void
    {
        // Notifier les collaborateurs si implémenté plus tard
    }

    private function getUsersToNotifyForBoard(Board $board, User $excludeUser): array
    {
        $users = collect([$board->user]);

        // Ajoute les collaborateurs ici quand tu auras cette fonctionnalité
        // $users = $users->merge($board->collaborators);

        return $users->filter(fn($user) => $user->id !== $excludeUser->id)->values()->all();
    }
}
