<?php

namespace App\Repositories\Contracts;

use App\Models\Column;

interface ColumnRepositoryInterface
{
    public function findById(int $id): ?Column;

    public function create(array $data): Column;

    public function update(Column $column, array $data): Column;

    public function delete(Column $column): void;
}
