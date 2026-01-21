<?php

namespace App\Exceptions;

class ForbiddenException extends BaseException
{
    protected int $statusCode = 403;
    protected string $errorType = 'forbidden';
}
