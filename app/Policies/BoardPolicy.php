<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;

class BoardPolicy
{
    /**
     * L'utilisateur peut-il voir la liste des boards ?
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * L'utilisateur peut-il voir ce board ?
     * Admin: peut voir tous les boards
     * Viewer: peut voir seulement ses boards
     */
    public function view(User $user, Board $board): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $board->user_id;
    }

    /**
     * L'utilisateur peut-il créer un board ?
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('boards.create');
    }

    /**
     * L'utilisateur peut-il modifier ce board ?
     */
    public function update(User $user, Board $board): bool
    {
        if (!$user->hasPermissionTo('boards.update')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $board->user_id;
    }

    /**
     * L'utilisateur peut-il supprimer ce board ?
     * Admin: oui
     * Viewer: non
     */
    public function delete(User $user, Board $board): bool
    {
        if (!$user->hasPermissionTo('boards.delete')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $board->user_id;
    }
}
