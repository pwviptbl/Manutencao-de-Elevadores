<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/api/health',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->alias([
            'tenant'    => \App\Http\Middleware\EnsureTenant::class,
            'role'      => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'=> \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\EnsureTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
