<?php

namespace App\Policies;

use App\Models\Card;
use App\Models\Column;
use App\Models\User;

class CardPolicy
{
    /**
     * L'utilisateur peut-il créer une carte dans cette colonne ?
     */
    public function create(User $user, Column $column): bool
    {
        return $user->id === $column->board->user_id;
    }

    /**
     * L'utilisateur peut-il modifier cette carte ?
     */
    public function update(User $user, Card $card): bool
    {
        return $user->id === $card->column->board->user_id;
    }

    /**
     * L'utilisateur peut-il supprimer cette carte ?
     */
    public function delete(User $user, Card $card): bool
    {
        return $user->id === $card->column->board->user_id;
    }

    /**
     * L'utilisateur peut-il déplacer cette carte ?
     */
    public function move(User $user, Card $card): bool
    {
        return $user->id === $card->column->board->user_id;
    }
}
