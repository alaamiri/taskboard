<?php

namespace App\Exceptions\Column;

use App\Exceptions\NotFoundException;

class ColumnNotFoundException extends NotFoundException
{
    protected string $errorType = 'column_not_found';

    public function __construct(int $columnId)
    {
        parent::__construct(
            "Column with ID {$columnId} not found.",
            ['column_id' => $columnId]
        );
    }
}
