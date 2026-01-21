<?php

namespace App\Exceptions;

class UnauthorizedActionException extends ForbiddenException
{
    protected string $errorType = 'unauthorized_action';

    public function __construct(string $action, string $resource, ?int $resourceId = null)
    {
        $message = "You are not authorized to {$action} this {$resource}.";
        
        $details = [
            'action' => $action,
            'resource' => $resource,
        ];

        if ($resourceId !== null) {
            $details['resource_id'] = $resourceId;
        }

        parent::__construct($message, $details);
    }
}
