<?php

namespace App\Repositories\Contracts;

use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Collection;

interface BoardRepositoryInterface
{
    public function findById(int $id): ?Board;
    
    public function findByIdWithRelations(int $id): ?Board;
    
    public function getAllForUser(User $user): Collection;
    
    public function create(array $data): Board;
    
    public function update(Board $board, array $data): Board;
    
    public function delete(Board $board): void;
}

