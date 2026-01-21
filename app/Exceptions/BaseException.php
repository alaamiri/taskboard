<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseException extends Exception
{
    protected int $statusCode = 400;
    protected string $errorType = 'error';
    protected array $details = [];

    public function __construct(string $message = '', array $details = [])
    {
        parent::__construct($message);
        $this->details = $details;
    }

    public function render(Request $request): JsonResponse|null
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => [
                    'type' => $this->errorType,
                    'message' => $this->getMessage(),
                    'details' => $this->details,
                ],
            ], $this->statusCode);
        }

        return null;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }
}
