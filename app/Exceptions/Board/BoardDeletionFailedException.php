<?php

namespace App\Exceptions\Board;

class BoardDeletionFailedException extends \Exception
{
    protected string $errorType = 'board_not_deleted';

    protected array $context = [];

    public function __construct(int $boardId)
    {
        $this->context = ['board_id' => $boardId];
        parent::__construct("Board with ID {$boardId} not deleted.");
    }

    public function context(): array
    {
        return $this->context;
    }
}
