<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Activitylog\Models\Activity;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\LogActivityContext::class,
        ]);

        //
    })
    ->booted(function () {
        // Login : 5 tentatives par minute
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->input('email') . '|' . $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => [
                            'type' => 'too_many_requests',
                            'message' => 'Trop de tentatives de connexion. Réessayez dans une minute.',
                        ],
                    ], 429);
                });
        });

        // Register : 3 par minute
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => [
                            'type' => 'too_many_requests',
                            'message' => 'Trop de tentatives d\'inscription. Réessayez dans une minute.',
                        ],
                    ], 429);
                });
        });

        // API standard : 60 par minute
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            // Admin : limite plus élevée
            if ($user?->hasRole('admin')) {
                return Limit::perMinute(120)->by($user->id);
            }

            // User connecté
            if ($user) {
                return Limit::perMinute(60)->by($user->id);
            }

            // Anonyme
            return Limit::perMinute(30)->by($request->ip());
        });

        // API écriture : 20 par minute
        RateLimiter::for('api-write', function (Request $request) {
            $user = $request->user();

            if ($user?->hasRole('admin')) {
                return Limit::perMinute(60)->by($user->id);
            }

            if ($user) {
                return Limit::perMinute(20)->by($user->id);
            }

            return Limit::perMinute(5)->by($request->ip());
        });

        // Données sensibles : 10 par minute
        RateLimiter::for('sensitive', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => [
                            'type' => 'too_many_requests',
                            'message' => 'Accès limité aux données sensibles. Réessayez plus tard.',
                        ],
                    ], 429);
                });
        });

        // Mot de passe oublié : 3 par minute
        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->input('email') . '|' . $request->ip());
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (\App\Exceptions\BaseException $e) {
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'type' => $e->getErrorType(),
                    'message' => $e->getMessage(),
                    'details' => $e->getDetails(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                ])
                ->log('exception');
        });
    })->create();
