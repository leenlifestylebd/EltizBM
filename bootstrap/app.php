<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'business'    => \App\Http\Middleware\BusinessMiddleware::class,
            'superadmin'  => \App\Http\Middleware\SuperAdminMiddleware::class,
            'permission'  => \App\Http\Middleware\PermissionMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhooks/woocommerce',
            'webhook/steadfast',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();