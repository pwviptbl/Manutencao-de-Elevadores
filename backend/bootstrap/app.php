<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/api/health',
        apiPrefix: 'api',
        then: function () {
            // API Pública v1 — autenticação por API Key, prefixo /api/v1/
            Route::middleware('api')
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(base_path('routes/api_v1.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->alias([
            // Rotas internas (Sanctum)
            'tenant'          => \App\Http\Middleware\EnsureTenant::class,
            'role'            => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'      => \Spatie\Permission\Middleware\PermissionMiddleware::class,

            // API Pública v1 (API Key)
            'api.key'         => \App\Http\Middleware\AuthenticateApiKey::class,
            'api.scope'       => \App\Http\Middleware\CheckApiScope::class,
            'api.rate-limit'  => \App\Http\Middleware\ApiRateLimiter::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\EnsureTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
