<?php

namespace App\Exceptions\Board;

use App\Exceptions\NotFoundException;

class BoardNotFoundException extends NotFoundException
{
    protected string $errorType = 'board_not_found';

    public function __construct(int $boardId)
    {
        parent::__construct(
            "Board with ID {$boardId} not found.",
            ['board_id' => $boardId]
        );
    }
}
