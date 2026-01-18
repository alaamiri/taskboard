<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\Column;
use App\Models\User;

class ColumnPolicy
{
    /**
     * L'utilisateur peut-il créer une colonne dans ce board ?
     */
    public function create(User $user, Board $board): bool
    {
        return $user->id === $board->user_id;
    }

    /**
     * L'utilisateur peut-il modifier cette colonne ?
     */
    public function update(User $user, Column $column): bool
    {
        return $user->id === $column->board->user_id;
    }

    /**
     * L'utilisateur peut-il supprimer cette colonne ?
     */
    public function delete(User $user, Column $column): bool
    {
        return $user->id === $column->board->user_id;
    }
}
