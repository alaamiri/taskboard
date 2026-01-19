<?php

namespace App\Repositories\Contracts;

use App\Models\Card;

interface CardRepositoryInterface
{
    public function findById(int $id): ?Card;

    public function create(array $data): Card;

    public function update(Card $card, array $data): Card;

    public function delete(Card $card): void;
}
