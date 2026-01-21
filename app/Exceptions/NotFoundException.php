<?php

namespace App\Exceptions;

class NotFoundException extends BaseException
{
    protected int $statusCode = 404;
    protected string $errorType = 'not_found';
}
