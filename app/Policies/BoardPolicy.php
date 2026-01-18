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
        return true; // Tout utilisateur connecté peut voir sa liste
    }

    /**
     * L'utilisateur peut-il voir ce board ?
     */
    public function view(User $user, Board $board): bool
    {
        return $user->id === $board->user_id;
    }

    /**
     * L'utilisateur peut-il créer un board ?
     */
    public function create(User $user): bool
    {
        return true; // Tout utilisateur connecté peut créer
    }

    /**
     * L'utilisateur peut-il modifier ce board ?
     */
    public function update(User $user, Board $board): bool
    {
        return $user->id === $board->user_id;
    }

    /**
     * L'utilisateur peut-il supprimer ce board ?
     */
    public function delete(User $user, Board $board): bool
    {
        return $user->id === $board->user_id;
    }
}
