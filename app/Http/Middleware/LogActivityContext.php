<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Activitylog\Facades\CauserResolver;
use Symfony\Component\HttpFoundation\Response;

class LogActivityContext extends Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        activity()->withProperties([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $next($request);
    }
}
