<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Baseline security headers on every web response, then set the tenant's
        // timezone for the request (only applies when BUSINESS_TIMEZONE_ENABLED=true).
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\SetBusinessTimezone::class,
        ]);

        $middleware->alias([
            'business'    => \App\Http\Middleware\BusinessMiddleware::class,
            'superadmin'  => \App\Http\Middleware\SuperAdminMiddleware::class,
            'permission'  => \App\Http\Middleware\PermissionMiddleware::class,
            'role'        => \App\Http\Middleware\RoleMiddleware::class,
            'bot.token'   => \App\Http\Middleware\BotTokenMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhooks/woocommerce',
            'webhook/steadfast',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();