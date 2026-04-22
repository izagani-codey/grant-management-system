<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Providers\AuthServiceProvider::class,
        App\Providers\AppServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if (($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? '') === 'testing') {
            // Keep feature tests deterministic: disable CSRF checks only in test environment.
            $middleware->validateCsrfTokens(except: ['*']);
        }

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'performance.monitor' => \App\Http\Middleware\PerformanceMonitoring::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
