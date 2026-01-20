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
        if (!$user->hasPermissionTo('columns.create')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $board->user_id;
    }

    /**
     * L'utilisateur peut-il modifier cette colonne ?
     */
    public function update(User $user, Column $column): bool
    {
        if (!$user->hasPermissionTo('columns.update')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $column->board->user_id;
    }

    /**
     * L'utilisateur peut-il supprimer cette colonne ?
     * Admin: oui
     * Viewer: non
     */
    public function delete(User $user, Column $column): bool
    {
        if (!$user->hasPermissionTo('columns.delete')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $column->board->user_id;
    }
}
