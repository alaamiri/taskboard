<?php

namespace App\Exceptions\Card;

use App\Exceptions\ForbiddenException;
use App\Models\Card;
use App\Models\Column;

class CannotMoveCardException extends ForbiddenException
{
    protected string $errorType = 'cannot_move_card';

    public function __construct(Card $card, Column $targetColumn, string $reason = 'Cannot move card to another board')
    {
        parent::__construct(
            $reason,
            [
                'card_id' => $card->id,
                'source_board_id' => $card->column->board_id,
                'target_board_id' => $targetColumn->board_id,
                'target_column_id' => $targetColumn->id,
            ]
        );
    }
}
