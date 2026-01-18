<?php

use App\Models\Board;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('board.{boardId}', function ($user, $boardId) {
    $board = Board::find($boardId);

    if (!$board || $board->user_id !== $user->id) {
        return false;
    }

    // Retourne les infos de l'utilisateur pour le PresenceChannel
    return [
        'id' => $user->id,
        'name' => $user->name
    ];
});
