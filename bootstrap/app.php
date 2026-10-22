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
        // All web endpoints use Laravel's session and CSRF middleware.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Keep API errors in JSON; validation exceptions are normalized in controllers.
    })
    ->create();
