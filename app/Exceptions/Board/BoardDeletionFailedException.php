<?php

namespace App\Exceptions\Board;

class BoardDeletionFailedException extends \Exception
{
    protected string $errorType = 'board_not_deleted';

    public function __construct(int $boardId)
    {
        parent::__construct(
            "Board with ID {$boardId} not deleted.",
            ['board_id' => $boardId]
        );
    }
}
