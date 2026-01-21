<?php

namespace App\Exceptions\Card;

use App\Exceptions\NotFoundException;

class CardNotFoundException extends NotFoundException
{
    protected string $errorType = 'card_not_found';

    public function __construct(int $cardId)
    {
        parent::__construct(
            "Card with ID {$cardId} not found.",
            ['card_id' => $cardId]
        );
    }
}
